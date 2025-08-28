<?php

namespace App\GraphQL\Queries;

use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use Ramsey\Collection\Collection;

class UserQuery
{
    private UserService $userService;
    private AuthService $authService;

    public function __construct(
        UserService $userservice,
        AuthService $authService
    ) {
        $this->userService = $userservice;
        $this->authService = $authService;
    }

    public function all(): Collection
    {
        return $this->userService->all();
    }

    public function find($_, array $args): ?User
    {
        return $this->userService->find((int) $args['id']);
    }

    public function me(): ?User
    {
        return $this->authService->me();
    }
}
