<?php

namespace App\Services;

use App\Enums\DomainError;
use App\Enums\TransactionType;
use App\Repositories\UserRepository;
use App\Exceptions\GraphQLClientException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BankService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $repo)
    {
        $this->userRepository = $repo;
    }
    /**
     * Cria uma conta no banco.
     *
     * @return array{conta:int,saldo:float}
     */
    public function criarConta(int $conta, float $saldoInicial = 0.0, ?int $userId = null): array
    {
        if ($conta <= 0) {
            throw new GraphQLClientException(
                'Número de conta inválido.',
                ['code' => DomainError::INVALID_ACCOUNT_NUMBER->value]
            );
        }
        if ($saldoInicial < 0) {
            throw new GraphQLClientException(
                'Saldo inicial não pode ser negativo.',
                ['code' => DomainError::INVALID_INITIAL_BALANCE->value]
            );
        }

        if ($this->existeConta($conta)) {
            throw new GraphQLClientException(
                'Conta já existe.',
                ['code' => DomainError::ACCOUNT_ALREADY_EXISTS->value]
            );
        }

        $saldo = $this->normalizeMoney($saldoInicial);

        $acc = $this->userRepository->createAccount([
            'user_id' => $userId,
            'number'  => $conta,
            'balance' => $saldo,
        ]);

        return $this->payload($acc->number, $acc->balance);
    }

    /**
     * Deposita um valor na conta.
     *
     * @return array{conta:int,saldo:float}
     */
    public function depositar(int $conta, float $valor): array
    {
        $amountCents = $this->amountToCentsOrFail(
            $valor,
            'Valor inválido para depósito.',
            DomainError::INVALID_DEPOSIT_AMOUNT
        );

        try {
            return $this->userRepository->transaction(function () use ($conta, $amountCents) {
                $acc = $this->findAccountOrFail($conta, true);

                $newBalanceCents = $this->toCents($acc->balance) + $amountCents;

                $acc->balance = $this->fromCents($newBalanceCents);
                $this->userRepository->saveAccount($acc);

                $this->userRepository->recordTransaction(
                    $acc,
                    TransactionType::DEPOSIT,
                    $this->fromCents($amountCents)
                );

                return $this->payload($acc->number, $acc->balance);
            });
        } catch (GraphQLClientException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new GraphQLClientException(
                'Falha ao processar depósito.',
                ['code' => DomainError::INTERNAL_ERROR->value]
            );
        }
    }

    /**
     * Saca um valor da conta.
     *
     * @return array{conta:int,saldo:float}
     */
    public function sacar(int $conta, float $valor): array
    {
        $amountCents = $this->amountToCentsOrFail(
            $valor,
            'Valor inválido para saque.',
            DomainError::INVALID_WITHDRAW_AMOUNT
        );

        try {
            return $this->userRepository->transaction(function () use ($conta, $amountCents) {
                $acc = $this->findAccountOrFail($conta, true);

                $currentCents = $this->toCents($acc->balance);
                if ($currentCents < $amountCents) {
                    throw new GraphQLClientException(
                        'Saldo insuficiente.',
                        ['code' => DomainError::INSUFFICIENT_FUNDS->value]
                    );
                }

                $acc->balance = $this->fromCents($currentCents - $amountCents);
                $this->userRepository->saveAccount($acc);

                $this->userRepository->recordTransaction(
                    $acc,
                    TransactionType::WITHDRAW,
                    $this->fromCents($amountCents)
                );

                return $this->payload($acc->number, $acc->balance);
            });
        } catch (GraphQLClientException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new GraphQLClientException(
                'Falha ao processar saque.',
                ['code' => DomainError::INTERNAL_ERROR->value]
            );
        }
    }

    public function saldo(int $conta): float
    {
        try {
            $acc = $this->findAccountOrFail($conta, false);
            return (float) $acc->balance;
        } catch (GraphQLClientException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new GraphQLClientException(
                'Falha ao consultar saldo.',
                ['code' => DomainError::INTERNAL_ERROR->value]
            );
        }
    }
    private function existeConta(int $conta): bool
    {
        try {
            $this->userRepository->findAccountByNumber($conta, false);
            return true;
        } catch (ModelNotFoundException) {
            return false;
        }
    }

    private function findAccountOrFail(int $conta, bool $forUpdate)
    {
        try {
            return $this->userRepository->findAccountByNumber($conta, $forUpdate);
        } catch (ModelNotFoundException) {
            throw new GraphQLClientException(
                'Conta não encontrada.',
                ['code' => DomainError::ACCOUNT_NOT_FOUND->value]
            );
        }
    }

    /** Converte e valida (>0) para centavos. */
    private function amountToCentsOrFail(float|int|string $valor, string $msg, DomainError $code): int
    {
        $float = (float) $valor;
        if ($float <= 0) {
            throw new GraphQLClientException($msg, ['code' => $code->value]);
        }
        return $this->toCents($float);
    }

    /** Normaliza reais -> centavos -> reais (duas casas). */
    private function normalizeMoney(float|int|string $valor): float
    {
        return $this->fromCents($this->toCents($valor));
    }

    private function toCents(float|int|string $value): int
    {
        return (int) round((float) $value * 100, 0, \PHP_ROUND_HALF_UP);
    }

    private function fromCents(int $cents): float
    {
        return (float) number_format($cents / 100, 2, '.', '');
    }

    private function payload(int|string $conta, float|int|string $saldo): array
    {
        return ['conta' => (int) $conta, 'saldo' => (float) $saldo];
    }
}
