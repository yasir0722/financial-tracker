<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\CarExpense;
use App\Models\Transaction;
use App\Models\Vehicle;
use App\Models\CarExpenseItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class CarMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_attach_car_expense_without_changing_transaction_amount(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $user = User::factory()->create();
        $bank = Bank::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();
        $transaction = Transaction::factory()->for($user)->create(['bank_id' => $bank->id, 'debit' => 1350, 'credit' => 0]);

        $response = $this->actingAs($user)->post(route('car-expenses.store'), [
            'transaction_id' => $transaction->id,
            'vehicle_id' => $vehicle->id,
            'service_date' => '2026-07-12',
            'odometer' => 92500,
            'workshop_new' => 'Toyota Service Centre',
            'note_title' => 'Full service',
            'foreman_technician' => 'Ahmad',
            'items' => [['category' => 'Engine Oil', 'item_name' => 'Engine Oil', 'brand' => 'Toyota', 'quantity' => 1, 'unit_price' => 200, 'labour_cost' => 50]],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('car_expenses', ['transaction_id' => $transaction->id, 'vehicle_id' => $vehicle->id]);
        $this->assertDatabaseHas('car_expense_items', ['item_name' => 'Engine Oil', 'total_price' => 250]);
        $this->assertSame('1350.00', $transaction->fresh()->debit);
    }

    public function test_user_cannot_attach_expense_to_another_users_transaction(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $bank = Bank::factory()->create();
        $vehicle = Vehicle::factory()->for($otherUser)->create();
        $transaction = Transaction::factory()->for($owner)->create(['bank_id' => $bank->id]);

        $this->actingAs($otherUser)->post(route('car-expenses.store'), [
            'transaction_id' => $transaction->id,
            'vehicle_id' => $vehicle->id,
            'service_date' => '2026-07-12',
            'items' => [[
                'category' => 'Other',
                'item_name' => 'Test',
                'quantity' => 1,
                'unit_price' => 10,
            ]],
        ])->assertForbidden();
    }
}
