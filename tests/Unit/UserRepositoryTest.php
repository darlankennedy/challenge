<?php

namespace Tests\Unit;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepository(new User());
    }

    /** -----------------------
     *  Métodos herdados (Base)
     *  ----------------------*/

    public function test_show_returns_user(): void
    {
        $user  = User::factory()->create(['name' => 'Ana']);
        $found = $this->repo->show($user->id);

        $this->assertNotNull($found);
        $this->assertEquals('Ana', $found->name);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_all_returns_collection_of_users(): void
    {
        User::factory()->count(2)->create();
        $all = $this->repo->all();

        $this->assertGreaterThanOrEqual(2, $all->count());
    }

    public function test_update_modifies_user_and_persists(): void
    {
        $user    = User::factory()->create(['name' => 'Old']);
        $updated = $this->repo->update($user->id, ['name' => 'New']);

        $this->assertNotNull($updated);
        $this->assertEquals('New', $updated->name);
        $this->assertEquals('New', User::find($user->id)->name);
    }

    public function test_delete_removes_user(): void
    {
        $user = User::factory()->create();
        $ok   = $this->repo->delete($user->id);

        $this->assertTrue($ok);
        $this->assertNull(User::find($user->id));
    }

    /** -----------------------
     *  Métodos específicos
     *  ----------------------*/

    public function test_create_account_creates_and_returns_account(): void
    {
        $user = User::factory()->create();

        $acc = $this->repo->createAccount([
            'user_id' => $user->id,
            'number'  => 12345,
            'balance' => 100.50,
        ]);

        $this->assertInstanceOf(Account::class, $acc);
        $this->assertSame(12345, (int) $acc->number);
        $this->assertSame(100.50, (float) $acc->balance);
        $this->assertDatabaseHas('accounts', ['user_id' => $user->id, 'number' => 12345, 'balance' => 100.50]);
    }

    public function test_findAccountByNumber_returns_account_when_exists(): void
    {
        $user   = User::factory()->create();
        $created = Account::create(['user_id' => $user->id, 'number' => 2222, 'balance' => 10.00]);

        $found = $this->repo->findAccountByNumber(2222);
        $this->assertSame($created->id, $found->id);
    }

    public function test_findAccountByNumber_with_forUpdate_true_returns_account(): void
    {
        // Em SQLite, lockForUpdate é no-op, mas exercita o branch.
        $user    = User::factory()->create();
        $created = Account::create(['user_id' => $user->id, 'number' => 3333, 'balance' => 10.00]);

        DB::beginTransaction();
        try {
            $found = $this->repo->findAccountByNumber(3333, true);
            $this->assertSame($created->id, $found->id);
        } finally {
            DB::rollBack();
        }
    }

    public function test_findAccountByNumber_throws_when_missing(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Conta 999999 não encontrada');
        $this->repo->findAccountByNumber(999999);
    }

    public function test_saveAccount_updates_and_persists(): void
    {
        $user = User::factory()->create();
        $acc  = Account::create(['user_id' => $user->id, 'number' => 4444, 'balance' => 1.00]);

        $acc->balance = 9.99;
        $this->repo->saveAccount($acc);

        $this->assertSame(9.99, (float) $acc->balance);
        $this->assertDatabaseHas('accounts', ['number' => 4444, 'balance' => 9.99]);
    }

    public function test_recordTransaction_creates_transaction_row(): void
    {
        $user = User::factory()->create();
        $acc  = Account::create(['user_id' => $user->id, 'number' => 5555, 'balance' => 0.00]);

        $tx = $this->repo->recordTransaction($acc, TransactionType::DEPOSIT, 25.30);

        $this->assertInstanceOf(Transaction::class, $tx);
        $this->assertSame($acc->id, $tx->account_id);
        // Se o Model tiver cast do enum, $tx->type será TransactionType; senão, uma string.
        $this->assertTrue(
            $tx->type === TransactionType::DEPOSIT ||
            (string) $tx->type === TransactionType::DEPOSIT->value
        );
        $this->assertSame(25.30, (float) $tx->amount);
        $this->assertDatabaseHas('transactions', [
            'account_id' => $acc->id,
            'amount'     => 25.30,
        ]);
    }

    public function test_transaction_executes_callback_and_commits(): void
    {
        $user = User::factory()->create();

        $result = $this->repo->transaction(function () use ($user) {
            $acc = Account::create(['user_id' => $user->id, 'number' => 6666, 'balance' => 7.00]);
            return ['ok' => true, 'id' => $acc->id];
        });

        $this->assertTrue($result['ok']);
        $this->assertDatabaseHas('accounts', ['number' => 6666]);
        $this->assertSame($result['id'], Account::where('number', 6666)->value('id'));
    }

    public function test_transaction_rolls_back_on_exception(): void
    {
        $user = User::factory()->create();

        try {
            $this->repo->transaction(function () use ($user) {
                Account::create(['user_id' => $user->id, 'number' => 7777, 'balance' => 1.23]);
                throw new \RuntimeException('boom');
            });
            $this->fail('Deveria ter lançado exceção');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertDatabaseMissing('accounts', ['number' => 7777]);
    }
}
