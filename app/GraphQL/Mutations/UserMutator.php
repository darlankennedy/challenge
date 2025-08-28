<?php

namespace App\GraphQL\Mutations;

use App\Service\UserService;

class UserMutator
{
    protected UserService $svc;

    public function __construct(UserService $svc)
    {
        $this->svc = $svc;
    }

    public function create($_, array $args)
    {
        try {
            return $this->svc->createUser($args);
        } catch (\DomainException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function update($_, array $args)
    {
        try {
            $id = $args['id'];
            return $this->svc->updateUser($id, $args);
        } catch (\DomainException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function delete($_, array $args): bool
    {
        try {
            return (bool) $this->svc->deleteUser($args['id']);
        } catch (\DomainException $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
