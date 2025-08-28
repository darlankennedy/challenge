<?php

namespace App\GraphQL\Mutations;

use App\Service\AuthService;

class AuthMutator
{
    protected AuthService $authService;
    public function __construct(AuthService $svc) {
        $this->authService = $svc;
    }

    public function register($_, array $args): array
    {
        try {
            $data  = $args["input"];
            return $this->authService->register($data);
        } catch (\DomainException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function login($_, array $args): array
    {
        try {
            return $this->authService->login($args['input']);
        } catch (\DomainException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function logout(): bool
    {
        return $this->authService->logout();
    }

    public function refresh(): array
    {
        return $this->authService->refresh();
    }


}
