<?php

namespace Tests\Unit;

use App\GraphQL\Queries\UserQuery;
use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Collection\Collection;
use Tests\TestCase;

final class UserQueryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function all_delegates_to_user_service_and_returns_collection(): void
    {
        $u1 = (new User())->forceFill(['id' => 1, 'name' => 'Alice']);
        $u2 = (new User())->forceFill(['id' => 2, 'name' => 'Bob']);

        $collection = new Collection(User::class, [$u1, $u2]);

        $userService = Mockery::mock(UserService::class);
        $authService = Mockery::mock(AuthService::class);

        $userService->shouldReceive('all')->once()->andReturn($collection);

        $query  = new UserQuery($userService, $authService);
        $result = $query->all();

        $this->assertSame($collection, $result);
        $this->assertCount(2, $result);
        $this->assertEquals(['Alice', 'Bob'], array_map(fn ($u) => $u->name, $result->toArray()));
    }

    #[Test]
    public function find_delegates_to_user_service_passing_casted_id_and_returns_user(): void
    {
        $expected = (new User())->forceFill(['id' => 5, 'name' => 'Carol']);

        $userService = Mockery::mock(UserService::class);
        $authService = Mockery::mock(AuthService::class);

        $userService->shouldReceive('find')->with(5)->once()->andReturn($expected);

        $query  = new UserQuery($userService, $authService);
        $result = $query->find(null, ['id' => '5']);

        $this->assertSame($expected, $result);
        $this->assertEquals(5, $result->id);
    }

    #[Test]
    public function me_delegates_to_auth_service_and_returns_user(): void
    {
        $me = (new User())->forceFill(['id' => 9, 'name' => 'Me']);

        $userService = Mockery::mock(UserService::class);
        $authService = Mockery::mock(AuthService::class);

        $authService->shouldReceive('me')->once()->andReturn($me);

        $query  = new UserQuery($userService, $authService);
        $result = $query->me();

        $this->assertSame($me, $result);
        $this->assertEquals('Me', $result->name);
    }
}
