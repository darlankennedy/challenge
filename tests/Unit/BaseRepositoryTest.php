<?php

namespace Tests\Unit;

use App\repository\BaseRepository;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class FakeModel extends Model
{
    protected $table = 'fakes';
    public $timestamps = false;
    protected $guarded = [];
}

class FakeThrowAllModel extends FakeModel
{
    public function newModelQuery()
    {
        throw new Exception('boom-all');
    }
}

class FakeThrowFindModel extends FakeModel
{
    public function newModelQuery()
    {
        throw new Exception('boom-find');
    }
}

class BaseRepositoryTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        // Banco em memória para os testes
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Cria a tabela usada nos testes
        Schema::create('fakes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('fakes');
        parent::tearDown();
    }

    public function all_returns_collection_of_models()
    {
        FakeModel::query()->create(['name' => 'A']);
        FakeModel::query()->create(['name' => 'B']);

        $repo = new BaseRepository(new FakeModel());

        $result = $repo->all();

        $this->assertCount(2, $result);
        $this->assertEquals(['A', 'B'], $result->pluck('name')->all());
    }

    public function all_on_exception_returns_empty_collection_and_logs_error()
    {
        Log::spy();

        $repo = new BaseRepository(new FakeThrowAllModel());

        $result = $repo->all();

        $this->assertCount(0, $result);
        Log::shouldHaveReceived('error')->once();
    }

    public function create_inserts_and_returns_model()
    {
        $repo = new BaseRepository(new FakeModel());

        $created = $repo->create(['name' => 'New']);

        $this->assertNotNull($created);
        $this->assertDatabaseHas('fakes', ['name' => 'New']);
    }

    public function create_on_exception_returns_null_and_logs_error()
    {
        Log::spy();

        $repo = new BaseRepository(new class extends FakeModel {
            public function create(array $attributes = [])
            {
                throw new Exception('boom-create');
            }
        });

        $created = $repo->create(['name' => 'X']);

        $this->assertNull($created);
        Log::shouldHaveReceived('error')->once();
    }

    public function show_returns_model_when_found()
    {
        $m = FakeModel::query()->create(['name' => 'ShowMe']);

        $repo = new BaseRepository(new FakeModel());

        $found = $repo->show($m->id);

        $this->assertNotNull($found);
        $this->assertEquals('ShowMe', $found->name);
    }

    public function show_returns_null_when_exception_and_logs_error()
    {
        Log::spy();

        $repo = new BaseRepository(new FakeThrowFindModel());

        $found = $repo->show(123);

        $this->assertNull($found);
        Log::shouldHaveReceived('error')->once();
    }

    public function update_changes_fields_and_returns_model()
    {
        $m = FakeModel::query()->create(['name' => 'Old']);
        $repo = new BaseRepository(new FakeModel());

        $updated = $repo->update($m->id, ['name' => 'New']);

        $this->assertNotNull($updated);
        $this->assertEquals('New', $updated->name);
        $this->assertDatabaseHas('fakes', ['id' => $m->id, 'name' => 'New']);
    }

    public function update_returns_null_and_logs_warning_when_not_found()
    {
        Log::spy();

        $repo = new BaseRepository(new FakeModel());
        $updated = $repo->update(9999, ['name' => 'Nope']);

        $this->assertNull($updated);
        Log::shouldHaveReceived('warning')->once()->with('not found', ['id' => 9999]);
    }

    public function update_returns_null_and_logs_error_on_exception()
    {
        Log::spy();

        $repo = new BaseRepository(new FakeThrowFindModel());

        $updated = $repo->update(1, ['name' => 'X']);

        $this->assertNull($updated);
        Log::shouldHaveReceived('error')->once();
    }

    public function delete_removes_record_and_returns_true()
    {
        $m = FakeModel::query()->create(['name' => 'Del']);
        $repo = new BaseRepository(new FakeModel());

        $deleted = $repo->delete($m->id);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('fakes', ['id' => $m->id]);
    }

    public function delete_returns_false_and_logs_warning_when_not_found()
    {
        Log::spy();

        $repo = new BaseRepository(new FakeModel());

        $deleted = $repo->delete(777);

        $this->assertFalse($deleted);
        Log::shouldHaveReceived('warning')->once()->with('not found', ['id' => 777]);
    }

    public function delete_returns_false_and_logs_error_on_exception()
    {
        Log::spy();

        $repo = new BaseRepository(new FakeThrowFindModel());

        $deleted = $repo->delete(1);

        $this->assertFalse($deleted);
        Log::shouldHaveReceived('error')->once();
    }
}

