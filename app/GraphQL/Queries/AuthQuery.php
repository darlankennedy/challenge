<?php declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Services\AuthService;

final readonly class AuthQuery
{
    protected AuthService $authService;
    public function __construct(AuthService $svc) {
        $this->authService = $svc;
    }

    public function me()
    {
        return $this->authService->me();
    }
}
