<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_belongs_to_account(): void
    {
        $account = Account::factory()->create();
        $tx = Transaction::factory()->create(['account_id' => $account->id]);

        $this->assertInstanceOf(Account::class, $tx->account);
        $this->assertEquals($account->id, $tx->account->id);
    }
}
