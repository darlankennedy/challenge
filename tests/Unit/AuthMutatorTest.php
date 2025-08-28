<?php

namespace Tests\Unit;

use App\GraphQL\Mutations\AuthMutator;
use App\Service\AuthService;
use DomainException;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class AuthMutatorTest extends TestCase
{
    /** @var AuthService&MockObject */
    private AuthService $svc;

    private AuthMutator $mutator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = $this->createMock(AuthService::class);
        $this->mutator = new AuthMutator($this->svc);
    }

    public function test_register_calls_service_with_input_and_returns_payload(): void
    {
        $args = [
            'input' => [
                'name' => 'Ana',
                'email' => 'ana@example.com',
                'password' => 'secret123',
            ],
        ];

        $expected = [
            'access_token' => 'jwt-token',
            'token_type'   => 'bearer',
            'expires_in'   => 3600,
            'user'         => (object) ['id' => 1, 'name' => 'Ana'],
        ];

        $this->svc->expects($this->once())
            ->method('register')
            ->with($this->equalTo($args['input']))
            ->willReturn($expected);

        $result = $this->mutator->register(null, $args);

        $this->assertSame($expected, $result);
    }

    public function test_register_converts_domain_exception_to_exception(): void
    {
        $args = ['input' => ['name'=>'Ana','email'=>'ana@example.com','password'=>'x']];

        $this->svc->expects($this->once())
            ->method('register')
            ->with($this->equalTo($args['input']))
            ->willThrowException(new \DomainException('Erro de domínio no register'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Erro de domínio no register');

        $this->mutator->register(null, $args);
    }

    public function test_login_returns_authpayload_shape(): void
    {
        $args = ['input' => ['email' => 'ana@example.com', 'password' => 'secret123']];
        $expected = [
            'access_token' => 'jwt-token',
            'token_type'   => 'bearer',
            'expires_in'   => 3600,
            'user'         => (object)['id'=>1,'name'=>'Ana'],
        ];

        $this->svc->expects($this->once())
            ->method('login')
            ->with($this->equalTo($args['input']))
            ->willReturn($expected);

        $out = $this->mutator->login(null, $args);

        $this->assertIsArray($out);
        $this->assertArrayHasKey('access_token', $out);
        $this->assertArrayHasKey('token_type', $out);
        $this->assertArrayHasKey('expires_in', $out);
        $this->assertArrayHasKey('user', $out);
        $this->assertSame($expected, $out);
    }

    public function test_logout_returns_bool_from_service(): void
    {
        $this->svc->expects($this->once())
            ->method('logout')
            ->with()
            ->willReturn(true);

        $ok = $this->mutator->logout();

        $this->assertTrue($ok);
    }

    public function test_refresh_returns_payload_from_service(): void
    {
        $expected = [
            'access_token' => 'new-jwt-token',
            'token_type'   => 'bearer',
            'expires_in'   => 3600,
            'user'         => (object) ['id' => 1, 'name' => 'Ana'],
        ];

        $this->svc->expects($this->once())
            ->method('refresh')
            ->with()
            ->willReturn($expected);

        $result = $this->mutator->refresh();

        $this->assertSame($expected, $result);
    }


    public function test_login_converts_domain_exception_to_exception(): void
    {
        $args = ['input' => ['email'=>'ana@example.com','password'=>'errada']];

        $this->svc->expects($this->once())
            ->method('login')
            ->with($this->equalTo($args['input']))
            ->willThrowException(new \DomainException('Credenciais inválidas.'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Credenciais inválidas.');

        $this->mutator->login(null, $args);
    }
}
