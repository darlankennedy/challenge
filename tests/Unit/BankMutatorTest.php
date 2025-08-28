<?php
namespace Tests\Unit;
use App\GraphQL\Mutations\BankMutator;
use App\Services\BankService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class BankMutatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeMutator(MockInterface $service): BankMutator
    {
        return new BankMutator($service);
    }

    public function test_depositar_delega_para_service_e_retorna_payload(): void
    {
        $args = ['conta' => '12345', 'valor' => '200.10'];

        $svc = Mockery::mock(BankService::class);
        $svc->shouldReceive('depositar')
            ->once()
            ->with(12345, 200.10)
            ->andReturn(['conta' => 12345, 'saldo' => 300.60]);

        $mut = $this->makeMutator($svc);
        $out = $mut->depositar(null, $args);

        $this->assertSame(['conta' => 12345, 'saldo' => 300.60], $out);
    }

    public function test_sacar_delega_para_service_e_retorna_payload(): void
    {
        $args = ['conta' => '998', 'valor' => '50'];

        $svc = Mockery::mock(BankService::class);
        $svc->shouldReceive('sacar')
            ->once()
            ->with(998, 50.0)
            ->andReturn(['conta' => 998, 'saldo' => 150.0]);

        $mut = $this->makeMutator($svc);
        $out = $mut->sacar(null, $args);

        $this->assertSame(['conta' => 998, 'saldo' => 150.0], $out);
    }

    public function test_saldo_delega_para_service_e_retorna_float(): void
    {
        $args = ['conta' => '777'];

        $svc = Mockery::mock(BankService::class);
        $svc->shouldReceive('saldo')
            ->once()
            ->with(777)
            ->andReturn(123.45);

        $mut = $this->makeMutator($svc);
        $out = $mut->saldo(null, $args);

        $this->assertSame(123.45, $out);
    }

    public function test_criar_conta_usa_user_id_do_argumento_quando_presente(): void
    {
        $args = ['conta' => '555', 'saldoInicial' => '10.5', 'user_id' => '42'];

        $svc = Mockery::mock(BankService::class);
        $svc->shouldReceive('criarConta')
            ->once()
            ->with(555, 10.5, 42)
            ->andReturn(['conta' => 555, 'saldo' => 10.5]);

        $mut = $this->makeMutator($svc);
        $out = $mut->criarConta(null, $args);

        $this->assertSame(['conta' => 555, 'saldo' => 10.5], $out);
    }

    public function test_criar_conta_usa_usuario_logado_quando_nao_passar_user_id(): void
    {
        $args = ['conta' => '1001', 'saldoInicial' => '0'];

        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('id')->once()->andReturn(99);

        $authManager = Mockery::mock(AuthFactory::class);
        $authManager->shouldReceive('guard')->with('api')->andReturn($guard);

        $this->app->instance('auth', $authManager);

        $svc = Mockery::mock(BankService::class);
        $svc->shouldReceive('criarConta')
            ->once()
            ->with(1001, 0.0, 99)
            ->andReturn(['conta' => 1001, 'saldo' => 0.0]);

        $mut = $this->makeMutator($svc);
        $out = $mut->criarConta(null, $args);

        $this->assertSame(['conta' => 1001, 'saldo' => 0.0], $out);
    }
}
