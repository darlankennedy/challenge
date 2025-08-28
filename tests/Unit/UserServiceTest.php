<?php

namespace Tests\Unit;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

final class UserServiceTest extends TestCase
{
    /** Helper: cria um User sem persistir */
    private function fakeUser(int $id, string $name, string $email): User
    {
        return (new User())->forceFill([
            'id'    => $id,
            'name'  => $name,
            'email' => $email,
        ]);
    }

    /** ---------------- getAllUsers ---------------- */

    public function test_get_all_users_returns_collection(): void
    {
        /** @var UserRepository&MockObject $repo */
        $repo = $this->createMock(UserRepository::class);

        $users = collect([
            $this->fakeUser(1, 'Ana', 'ana@example.com'),
            $this->fakeUser(2, 'Bob', 'bob@example.com'),
        ]);

        $repo->method('all')->willReturn($users);

        $svc = new UserService($repo);
        $out = $svc->getAllUsers();

        $this->assertInstanceOf(Collection::class, $out);
        $this->assertCount(2, $out);
        $this->assertSame('Ana', $out[0]->name);
    }

    public function test_get_all_users_throws_on_error(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('all')->willThrowException(new \RuntimeException('db down'));

        $svc = new UserService($repo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Não foi possível listar usuários.');
        $svc->getAllUsers();
    }

    /** ---------------- createUser ---------------- */

    public function test_create_user_passes_data_and_returns_model(): void
    {
        $data = ['name' => 'Carol', 'email' => 'carol@example.com'];
        $created = $this->fakeUser(10, 'Carol', 'carol@example.com');

        $repo = $this->createMock(UserRepository::class);
        $repo->method('create')->with($data)->willReturn($created);

        $svc = new UserService($repo);
        $out = $svc->createUser($data);

        $this->assertNotNull($out);
        $this->assertSame('Carol', $out->name);
    }

    public function test_create_user_throws_on_error(): void
    {
        $data = ['name' => 'Carol', 'email' => 'carol@example.com'];

        $repo = $this->createMock(UserRepository::class);
        $repo->method('create')->with($data)->willThrowException(new \RuntimeException('db down'));

        $svc = new UserService($repo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Não foi possível criar o usuário.');
        $svc->createUser($data);
    }

    /** ---------------- updateUser ---------------- */

    public function test_update_user_passes_id_and_data_and_returns_model(): void
    {
        $updated = $this->fakeUser(7, 'New Name', 'new@example.com');

        $repo = $this->createMock(UserRepository::class);
        $repo->expects($this->once())
            ->method('update')
            ->with(7, ['name' => 'New Name'])
            ->willReturn($updated);

        $svc = new UserService($repo);
        $out = $svc->updateUser(7, ['name' => 'New Name']);

        $this->assertNotNull($out);
        $this->assertSame('New Name', $out->name);
    }

    public function test_update_user_throws_when_not_found(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('update')
            ->with(999, ['name' => 'X'])
            ->willThrowException(new ModelNotFoundException());

        $svc = new UserService($repo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Usuário não encontrado para atualização.');
        $svc->updateUser(999, ['name' => 'X']);
    }

    public function test_update_user_throws_on_error(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('update')
            ->with(5, ['name' => 'Z'])
            ->willThrowException(new \RuntimeException('db down'));

        $svc = new UserService($repo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Não foi possível atualizar o usuário.');
        $svc->updateUser(5, ['name' => 'Z']);
    }

    /** ---------------- deleteUser ---------------- */

    public function test_delete_user_returns_true_on_success(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('delete')->with(1)->willReturn(true);

        $svc = new UserService($repo);
        $this->assertTrue($svc->deleteUser(1));
    }

    public function test_delete_user_throws_when_not_found(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('delete')->with(999)->willThrowException(new ModelNotFoundException());

        $svc = new UserService($repo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Usuário não encontrado para exclusão.');
        $svc->deleteUser(999);
    }

    public function test_delete_user_throws_on_error(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('delete')->with(2)->willThrowException(new \RuntimeException('db down'));

        $svc = new UserService($repo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Não foi possível deletar o usuário.');
        $svc->deleteUser(2);
    }

    /** ---------------- getUserById ---------------- */

    public function test_get_user_by_id_returns_model(): void
    {
        $user = $this->fakeUser(7, 'Ana', 'ana@example.com');

        $repo = $this->createMock(UserRepository::class);
        $repo->method('show')->with(7)->willReturn($user);

        $svc = new UserService($repo);
        $out = $svc->getUserById(7);

        $this->assertNotNull($out);
        $this->assertSame(7, $out->id);
        $this->assertSame('Ana', $out->name);
    }

    public function test_get_user_by_id_returns_null_when_repo_returns_null(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('show')->with(123)->willReturn(null);

        $svc = new UserService($repo);
        $out = $svc->getUserById(123);

        $this->assertNull($out);
    }

    public function test_get_user_by_id_throws_when_not_found(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('show')->with(999)->willThrowException(new ModelNotFoundException());

        $svc = new UserService($repo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Usuário não encontrado.');
        $svc->getUserById(999);
    }

    public function test_get_user_by_id_throws_on_error(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('show')->with(5)->willThrowException(new \RuntimeException('db down'));

        $svc = new UserService($repo);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Não foi possível buscar o usuário.');
        $svc->getUserById(5);
    }
}
