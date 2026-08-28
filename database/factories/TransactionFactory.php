<?php

namespace Database\Factories;

use App\Models\Bank;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'posted_date' => $this->faker->date(), 'transaction_date' => $this->faker->date(), 'transaction_detail' => $this->faker->sentence(3), 'debit' => $this->faker->randomFloat(2, 0, 2000), 'credit' => 0, 'bank_id' => Bank::factory(), 'is_locked' => false];
    }
}
