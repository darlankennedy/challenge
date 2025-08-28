<?php

namespace Tests\Unit;

use App\Models\User;
use App\Service\AuthService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{

    use RefreshDatabase;

    public function test_register_user_successfully()
    {
        $svc = app(AuthService::class);

        $payload = $svc->register([
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'minhaSenha123',
        ]);

        $this->assertArrayHasKey('access_token', $payload);
        $this->assertArrayHasKey('token_type', $payload);
        $this->assertArrayHasKey('expires_in', $payload);
        $this->assertArrayHasKey('user', $payload);

        $user = User::whereEmail('ana@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals($payload['user']->id, $user->id);
        $this->assertEquals('bearer', $payload['token_type']);
        $this->assertGreaterThan(0, $payload['expires_in']);

    }

    public function test_login_successfully()
    {
        $user = User::factory()->create([
            'email' => 'ana@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $svc = app(AuthService::class);

        $payload = $svc->login([
            'email' => 'ana@example.com',
            'password' => 'secret123',
        ]);

        $this->assertArrayHasKey('access_token', $payload);
        $this->assertEquals($payload['user']->id, $user->id);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'ana@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $svc = app(AuthService::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Credenciais inválidas.');

        $svc->login([
            'email' => 'ana@example.com',
            'password' => 'errada',
        ]);
    }

    public function test_logout_successfully()
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $token = auth('api')->login($user);
        $this->assertIsString($token);

        $svc = app(AuthService::class);
        $ok = $svc->logout();

        $this->assertTrue($ok);
    }


    public function test_refresh_returns_new_token()
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $oldToken = auth('api')->login($user);
        $this->assertIsString($oldToken);

        $svc = app(AuthService::class);
        $payload = $svc->refresh();

        $this->assertArrayHasKey('access_token', $payload);
        $this->assertNotEmpty($payload['access_token']);
        $this->assertArrayHasKey('user', $payload);
        $this->assertEquals($user->id, $payload['user']->id);
    }

    public function test_me_returns_authenticated_user()
    {
        $user = User::factory()->create(['password' => bcrypt('x')]);
        auth('api')->login($user);

        $svc = app(AuthService::class);
        $me = $svc->me();

        $this->assertNotNull($me);
        $this->assertEquals($user->id, $me->id);
    }

    public function test_me_returns_null_if_not_authenticated()
    {
        $svc = app(AuthService::class);
        $me = $svc->me();

        $this->assertNull($me);
    }



}
