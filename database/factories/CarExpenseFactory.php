<?php

namespace Database\Factories;

use App\Models\CarExpense;
use App\Models\Transaction;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarExpenseFactory extends Factory
{
    protected $model = CarExpense::class;
    public function definition(): array
    {
        return ['transaction_id' => Transaction::factory(), 'vehicle_id' => Vehicle::factory(), 'service_date' => $this->faker->date(), 'odometer' => $this->faker->numberBetween(10000, 150000), 'workshop' => 'Toyota Service Centre', 'invoice_number' => strtoupper($this->faker->bothify('INV-#####')), 'notes' => null];
    }
}
