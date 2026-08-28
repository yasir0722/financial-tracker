<?php

namespace Database\Factories;

use App\Models\CarExpense;
use App\Models\CarExpenseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarExpenseItemFactory extends Factory
{
    protected $model = CarExpenseItem::class;
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 4);
        $unitPrice = $this->faker->randomFloat(2, 20, 500);
        $labour = $this->faker->randomFloat(2, 0, 150);
        return ['car_expense_id' => CarExpense::factory(), 'category' => 'Engine Oil', 'item_name' => 'Engine Oil', 'brand' => 'Toyota', 'model' => 'Synthetic', 'quantity' => $quantity, 'unit_price' => $unitPrice, 'labour_cost' => $labour, 'total_price' => ($quantity * $unitPrice) + $labour];
    }
}
