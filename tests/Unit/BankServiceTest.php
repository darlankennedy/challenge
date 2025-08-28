<?php
namespace Tests\Unit;
use App\Enums\DomainError;
use App\Enums\TransactionType;
use App\Exceptions\GraphQLClientException;
use App\Models\Account;
use App\Repositories\UserRepository;
use App\Services\BankService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class BankServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** Helper para instanciar o service com mock. */
    private function makeService(MockInterface $repo): BankService
    {
        return new BankService($repo);
    }

    /** Helper simples de "Account". */
    private function makeAccount(int $number, float $balance, ?int $userId = null): Account
    {
        $acc = new Account();
        $acc->setAttribute('id', 1);
        $acc->setAttribute('user_id', $userId);
        $acc->setAttribute('number', $number);
        $acc->setAttribute('balance', (float) number_format($balance, 2, '.', ''));
        return $acc;
    }


    public function test_criar_conta_sucesso(): void
    {
        $conta        = 12345;
        $saldoInicial = 100.50;
        $userId       = 42;

        $repo = Mockery::mock(UserRepository::class);

        // existeConta() -> false
        $repo->shouldReceive('findAccountByNumber')
            ->with($conta, false)
            ->andThrow(new ModelNotFoundException());

        // createAccount deve retornar um Account (não stdClass)
        $repo->shouldReceive('createAccount')
            ->once()
            ->with(Mockery::on(function ($data) use ($conta, $saldoInicial, $userId) {
                $balanceNormalized = (float) number_format($saldoInicial, 2, '.', '');
                return isset($data['user_id'], $data['number'], $data['balance'])
                    && $data['user_id'] === $userId
                    && $data['number']  === $conta
                    && (float) $data['balance'] === $balanceNormalized;
            }))
            ->andReturn($this->makeAccount($conta, $saldoInicial, $userId)); // <-- retorna Account

        $svc = $this->makeService($repo);
        $out = $svc->criarConta($conta, $saldoInicial, $userId);

        $this->assertSame($conta, $out['conta']);
        $this->assertSame($saldoInicial, $out['saldo']);
    }

    public function test_criar_conta_numero_invalido(): void
    {
        $repo = Mockery::mock(UserRepository::class);
        $svc  = $this->makeService($repo);

        try {
            $svc->criarConta(0, 10.0);
            $this->fail('Esperava GraphQLClientException');
        } catch (GraphQLClientException $e) {
            $this->assertSame(DomainError::INVALID_ACCOUNT_NUMBER->value, $e->getExtensions()['code'] ?? null);
        }
    }

    public function test_criar_conta_saldo_inicial_negativo(): void
    {
        $repo = Mockery::mock(UserRepository::class);
        $svc  = $this->makeService($repo);

        try {
            $svc->criarConta(123, -1.0);
            $this->fail('Esperava GraphQLClientException');
        } catch (GraphQLClientException $e) {
            $this->assertSame(DomainError::INVALID_INITIAL_BALANCE->value, $e->getExtensions()['code'] ?? null);
        }
    }

    public function test_criar_conta_ja_existe(): void
    {
        $conta = 123;

        $repo = Mockery::mock(UserRepository::class);
        // existeConta() -> true (repo NÃO lança, retorna algo)
        $repo->shouldReceive('findAccountByNumber')->with($conta, false)->andReturn($this->makeAccount($conta, 0.0));
        // não deve criar
        $repo->shouldNotReceive('createAccount');

        $svc = $this->makeService($repo);

        try {
            $svc->criarConta($conta, 0.0);
            $this->fail('Esperava GraphQLClientException');
        } catch (GraphQLClientException $e) {
            $this->assertSame(DomainError::ACCOUNT_ALREADY_EXISTS->value, $e->getExtensions()['code'] ?? null);
        }
    }

    public function test_depositar_sucesso(): void
    {
        $conta = 12345;
        $valor = 200.00;
        $acc   = $this->makeAccount($conta, 100.50);

        $repo = Mockery::mock(UserRepository::class);

        $repo->shouldReceive('transaction')->once()->andReturnUsing(fn(\Closure $cb) => $cb());
        $repo->shouldReceive('findAccountByNumber')->with($conta, true)->andReturn($acc);
        $repo->shouldReceive('saveAccount')->once();
        $repo->shouldReceive('recordTransaction')
            ->once()
            ->with($acc, TransactionType::DEPOSIT, 200.00);

        $svc = $this->makeService($repo);
        $out = $svc->depositar($conta, $valor);

        $this->assertSame($conta, $out['conta']);
        $this->assertSame(300.50, $out['saldo']);
    }

    public function test_depositar_valor_invalido(): void
    {
        $repo = Mockery::mock(UserRepository::class);
        $svc  = $this->makeService($repo);

        try {
            $svc->depositar(123, 0);
            $this->fail('Esperava GraphQLClientException');
        } catch (GraphQLClientException $e) {
            $this->assertSame(DomainError::INVALID_DEPOSIT_AMOUNT->value, $e->getExtensions()['code'] ?? null);
        }
    }

    public function test_sacar_sucesso(): void
    {
        $conta = 777;
        $valor = 100.00;
        $acc   = $this->makeAccount($conta, 300.00);

        $repo = Mockery::mock(UserRepository::class);
        $repo->shouldReceive('transaction')->once()->andReturnUsing(fn(\Closure $cb) => $cb());
        $repo->shouldReceive('findAccountByNumber')->with($conta, true)->andReturn($acc);
        $repo->shouldReceive('saveAccount')->once();
        $repo->shouldReceive('recordTransaction')->once()->with($acc, TransactionType::WITHDRAW, 100.00);

        $svc = $this->makeService($repo);
        $out = $svc->sacar($conta, $valor);

        $this->assertSame($conta, $out['conta']);
        $this->assertSame(200.00, $out['saldo']); // 300.00 - 100.00
    }

    public function test_sacar_saldo_insuficiente(): void
    {
        $conta = 888;
        $valor = 500.00;
        $acc   = $this->makeAccount($conta, 100.00);

        $repo = Mockery::mock(UserRepository::class);
        $repo->shouldReceive('transaction')->once()->andReturnUsing(fn(\Closure $cb) => $cb());
        $repo->shouldReceive('findAccountByNumber')->with($conta, true)->andReturn($acc);

        $svc = $this->makeService($repo);

        try {
            $svc->sacar($conta, $valor);
            $this->fail('Esperava GraphQLClientException');
        } catch (GraphQLClientException $e) {
            $this->assertSame(DomainError::INSUFFICIENT_FUNDS->value, $e->getExtensions()['code'] ?? null);
        }
    }

    public function test_saldo_sucesso(): void
    {
        $conta = 999;
        $acc   = $this->makeAccount($conta, 123.45);

        $repo = Mockery::mock(UserRepository::class);
        $repo->shouldReceive('findAccountByNumber')->with($conta, false)->andReturn($acc);

        $svc = $this->makeService($repo);
        $saldo = $svc->saldo($conta);

        $this->assertSame(123.45, $saldo);
    }

    public function test_saldo_conta_nao_encontrada(): void
    {
        $conta = 404;

        $repo = Mockery::mock(UserRepository::class);
        $repo->shouldReceive('findAccountByNumber')->with($conta, false)->andThrow(new ModelNotFoundException());

        $svc = $this->makeService($repo);

        try {
            $svc->saldo($conta);
            $this->fail('Esperava GraphQLClientException');
        } catch (GraphQLClientException $e) {
            $this->assertSame(DomainError::ACCOUNT_NOT_FOUND->value, $e->getExtensions()['code'] ?? null);
        }
    }

}
