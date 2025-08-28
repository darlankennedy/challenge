<?php
namespace Tests\Unit;
use App\GraphQL\Queries\AuthQuery;
use App\Models\User;
use App\Services\AuthService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuthQueryTest extends TestCase
{
    private function fakeUser(int $id, string $name, string $email): User
    {
        return (new User())->forceFill([
            'id'    => $id,
            'name'  => $name,
            'email' => $email,
        ]);
    }

    #[Test]
    public function me_returns_user_when_authenticated(): void
    {
        $user = $this->fakeUser(1, 'Ana', 'ana@example.com');

        $authSvc = $this->createMock(AuthService::class);
        $authSvc->expects($this->once())
            ->method('me')
            ->willReturn($user);

        $query = new AuthQuery($authSvc);

        $out = $query->me();

        $this->assertInstanceOf(User::class, $out);
        $this->assertSame('Ana', $out->name);
        $this->assertSame('ana@example.com', $out->email);
    }

    #[Test]
    public function me_returns_null_when_not_authenticated(): void
    {
        $authSvc = $this->createMock(AuthService::class);
        $authSvc->expects($this->once())
            ->method('me')
            ->willReturn(null);

        $query = new AuthQuery($authSvc);

        $out = $query->me();

        $this->assertNull($out);
    }
}
