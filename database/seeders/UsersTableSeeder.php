<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $usersData = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Normal User',
                'email' => 'user@example.com',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($usersData as $data) {
            $user = User::updateOrCreate(['email' => $data['email']], $data);

            // cria uma conta ligada a esse usuário
            $account = Account::factory()
                ->for($user) // seta user_id automático
                ->state([
                    'number' => $this->generateUniqueAccountNumber(),
                    // 'balance' => fake()->randomFloat(2, 100, 10000), // opcional: pode deixar na factory
                ])
                ->create();

            // cria transações ligadas à conta
            Transaction::factory(rand(3, 10))
                ->state(['account_id' => $account->id])
                ->create();
        }

        // completa até ter pelo menos 5 usuários
        $target = 5;
        $current = User::count();
        if ($current < $target) {
            User::factory($target - $current)->create()->each(function ($user) {
                $account = Account::factory()
                    ->for($user)
                    ->state(['number' => $this->generateUniqueAccountNumber()])
                    ->create();

                Transaction::factory(rand(3, 10))
                    ->state(['account_id' => $account->id])
                    ->create();
            });
        }
    }

    protected function generateUniqueAccountNumber(): string
    {
        do {
            $number = fake()->unique()->numerify('#####');
        } while (Account::where('number', $number)->exists());

        return $number;
    }
}
