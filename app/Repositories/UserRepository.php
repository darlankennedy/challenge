<?php

namespace App\Repositories;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function createAccount(array $data): Account
    {
        return Account::create($data);
    }

    public function findAccountByNumber(int $number, bool $forUpdate = false): Account
    {
        $query = Account::where('number', $number);
        if ($forUpdate) {
            $query->lockForUpdate();
        }
        $account = $query->first();
        if (!$account) {
            throw new ModelNotFoundException("Conta {$number} não encontrada");
        }
        return $account;
    }

    public function saveAccount(Account $account): void
    {
        $account->save();
    }

    public function recordTransaction(
        Account $account,
        TransactionType $type,
        float $amount,
    ): Transaction
    {
        return Transaction::create([
            'account_id'    => $account->id,
            'type'          => $type,
            'amount'        => $amount,
        ]);
    }

    public function transaction(\Closure $callback)
    {
        return DB::transaction($callback);
    }

}
