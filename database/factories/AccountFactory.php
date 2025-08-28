<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'number'  => $this->faker->unique()->numerify('#####'),
            'balance' => $this->faker->randomFloat(2, 100, 10000),
        ];
    }
}
