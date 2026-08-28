<?php

namespace Database\Factories;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankFactory extends Factory
{
    protected $model = Bank::class;
    public function definition(): array
    {
        return ['name' => $this->faker->company(), 'type' => true, 'is_investment' => false];
    }
}
