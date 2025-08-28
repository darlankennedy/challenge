<?php

namespace App\GraphQL\Mutations;

use App\Services\BankService;

final class BankMutator
{
    protected BankService $bankService;
    public function __construct(
        BankService $service
    ) {
        $this->bankService = $service;
    }

    public function depositar($_, array $args): array
    {
        return $this->bankService->depositar((int)$args['conta'], (float)$args['valor']);
    }

    public function sacar($_, array $args): array
    {
        return $this->bankService->sacar((int)$args['conta'], (float)$args['valor']);
    }

    public function saldo($_, array $args): float
    {
        return $this->bankService->saldo((int)$args['conta']);
    }

    public function criarConta($_, array $args): array
    {
        $userLogado = auth('api')->id();
        $conta        = (int)   $args['conta'];
        $saldoInicial = (float) ($args['saldoInicial'] ?? 0);
        $userId       = isset($args['user_id']) ? (int)$args['user_id'] : $userLogado;


        return $this->bankService->criarConta($conta, $saldoInicial, $userId);
    }
}
