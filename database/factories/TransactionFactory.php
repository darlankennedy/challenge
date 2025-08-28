<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['deposit', 'withdraw']);
        $amount = $this->faker->randomFloat(2, 1, 1000);

        return [
            'account_id' => Account::factory(),
            'type'       => $type,
            'amount'     => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
