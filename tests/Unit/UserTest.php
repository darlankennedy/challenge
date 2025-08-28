<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_jwt_identifier_and_claims(): void
    {
        $user = User::factory()->create();

        $this->assertEquals($user->id, $user->getJWTIdentifier());
        $this->assertSame([], $user->getJWTCustomClaims());
    }

    public function test_account_relationship(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(Account::class, $user->account);
        $this->assertEquals($account->id, $user->account->id);
    }

    public function test_hidden_and_casts(): void
    {
        $user = User::factory()->create([
            'password' => 'secret123',
        ]);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);

        // casts
        $this->assertIsArray($user->getCasts());
        $this->assertArrayHasKey('password', $user->getCasts());
    }
}
