<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $account->user);
        $this->assertEquals($user->id, $account->user->id);
    }

    public function test_account_has_transactions(): void
    {
        $account = Account::factory()->create();
        $tx = Transaction::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($account->transactions->contains($tx));
    }
}
