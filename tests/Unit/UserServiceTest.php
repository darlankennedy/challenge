<?php

namespace Tests\Unit;

use App\Service\UserService;
use App\repository\UserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    private UserRepository $repo;

    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = $this->createMock(UserRepository::class);
        $this->service = new UserService($this->repo);
    }

    public function test_get_all_users_returns_array(): void
    {
        $expected = [
            ['id' => 1, 'name' => 'Ana'],
            ['id' => 2, 'name' => 'Bruno'],
        ];

        $this->repo->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn($expected);

        $result = $this->service->getAllUsers();

        $this->assertSame($expected, $result);
    }

    public function test_create_user_passes_data_and_returns_created(): void
    {
        $input = ['name' => 'Carla', 'email' => 'carla@example.com'];
        $created = ['id' => 10, 'name' => 'Carla', 'email' => 'carla@example.com'];

        $this->repo->expects($this->once())
            ->method('create')
            ->with($this->equalTo($input))
            ->willReturn($created);

        $result = $this->service->createUser($input);

        $this->assertSame($created, $result);
    }

    public function test_update_user_passes_id_and_data_and_returns_updated(): void
    {
        $id = 5;
        $data = ['name' => 'Daniel'];
        $updated = ['id' => 5, 'name' => 'Daniel'];

        $this->repo->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo($id),
                $this->equalTo($data)
            )
            ->willReturn($updated);

        $result = $this->service->updateUser($id, $data);

        $this->assertSame($updated, $result);
    }

    public function test_delete_user_passes_id_and_returns_boolean(): void
    {
        $id = 7;

        $this->repo->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($id))
            ->willReturn(true);

        $result = $this->service->deleteUser($id);

        $this->assertTrue($result);
    }

    public function test_get_user_by_id_returns_entity(): void
    {
        $id = 3;
        $user = ['id' => 3, 'name' => 'Eva'];

        $this->repo->expects($this->once())
            ->method('show')
            ->with($this->equalTo($id))
            ->willReturn($user);

        $result = $this->service->getUserById($id);

        $this->assertSame($user, $result);
    }
}
