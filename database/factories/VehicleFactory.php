<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'name' => 'Myvi 1.5 AV', 'plate_number' => strtoupper($this->faker->bothify('??? ####')), 'manufacturer' => 'Perodua', 'model' => 'Myvi', 'variant' => '1.5 AV', 'year' => 2022, 'current_odometer' => 90000, 'is_default' => false];
    }
}
