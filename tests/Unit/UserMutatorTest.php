<?php

namespace Tests\Unit;

use App\GraphQL\Mutations\UserMutator;
use App\Service\UserService;
use DomainException;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class UserMutatorTest extends TestCase
{
    /** @var UserService&MockObject */
    private UserService $svc;

    private UserMutator $mutator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = $this->createMock(UserService::class);
        $this->mutator = new UserMutator($this->svc);
    }

    public function test_create_returns_payload_from_service(): void
    {
        $args = ['name' => 'Ana', 'email' => 'ana@example.com'];
        $expected = ['id' => 1, 'name' => 'Ana', 'email' => 'ana@example.com'];

        $this->svc->expects($this->once())
            ->method('createUser')
            ->with($this->equalTo($args))
            ->willReturn($expected);

        $out = $this->mutator->create(null, $args);

        $this->assertSame($expected, $out);
    }

    public function test_create_converts_domain_exception_to_exception(): void
    {
        $args = ['name' => 'Ana'];

        $this->svc->expects($this->once())
            ->method('createUser')
            ->willThrowException(new DomainException('erro ao criar'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('erro ao criar');

        $this->mutator->create(null, $args);
    }
    public function test_update_calls_service_with_id_and_args_and_returns_payload(): void
    {
        $args = ['id' => 10, 'name' => 'Novo'];
        $expected = ['id' => 10, 'name' => 'Novo'];

        $this->svc->expects($this->once())
            ->method('updateUser')
            ->with(
                $this->equalTo(10),
                $this->equalTo($args)
            )
            ->willReturn($expected);

        $out = $this->mutator->update(null, $args);

        $this->assertSame($expected, $out);
    }

    public function test_update_converts_domain_exception_to_exception(): void
    {
        $args = ['id' => 99, 'name' => 'X'];

        $this->svc->expects($this->once())
            ->method('updateUser')
            ->willThrowException(new DomainException('não encontrado'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('não encontrado');

        $this->mutator->update(null, $args);
    }

    public function test_delete_returns_true_when_service_returns_truthy(): void
    {
        $args = ['id' => 7];

        $this->svc->expects($this->once())
            ->method('deleteUser')
            ->with(7)
            ->willReturn(true);

        $ok = $this->mutator->delete(null, $args);

        $this->assertTrue($ok);
    }

    public function test_delete_returns_false_when_service_returns_falsy(): void
    {
        $args = ['id' => 404];

        $this->svc->expects($this->once())
            ->method('deleteUser')
            ->with(404)
            ->willReturn(false);

        $ok = $this->mutator->delete(null, $args);

        $this->assertFalse($ok);
    }

    public function test_delete_converts_domain_exception_to_exception(): void
    {
        $args = ['id' => 1];

        $this->svc->expects($this->once())
            ->method('deleteUser')
            ->willThrowException(new DomainException('falha ao remover'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('falha ao remover');

        $this->mutator->delete(null, $args);
    }
}
