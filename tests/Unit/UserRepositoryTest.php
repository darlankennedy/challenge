<?php

namespace Tests\Unit;

use App\Models\User;
use App\repository\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepository(new User());
    }

    public function test_show_returns_user()
    {
        $user = User::factory()->create(['name' => 'Ana']);
        $found = $this->repo->show($user->id);

        $this->assertNotNull($found);
        $this->assertEquals('Ana', $found->name);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_all_returns_collection_of_users()
    {
        User::factory()->count(2)->create();
        $all = $this->repo->all();

        $this->assertGreaterThanOrEqual(2, $all->count());
    }

    public function test_update_modifies_user_and_persists()
    {
        $user = User::factory()->create(['name' => 'Old']);
        $updated = $this->repo->update($user->id, ['name' => 'New']);

        $this->assertNotNull($updated);
        $this->assertEquals('New', $updated->name);

        $this->assertEquals('New', User::find($user->id)->name);
    }

    public function test_delete_removes_user()
    {
        $user = User::factory()->create();
        $ok = $this->repo->delete($user->id);

        $this->assertTrue($ok);
        $this->assertNull(User::find($user->id));
    }
}
