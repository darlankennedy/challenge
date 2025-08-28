<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use DomainException;

class AuthService
{
    public function register(array $data): array
    {
        $this->validateRegister($data);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = auth('api')->login($user);

        return $this->tokenResponse($token, $user);
    }

    public function login(array $credentials): array
    {
        $this->validateLogin($credentials);

        if (! $token = auth('api')->attempt([
            'email'    => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            throw new DomainException('Credenciais inválidas.');
        }

        return $this->tokenResponse($token, auth('api')->user());
    }

    public function logout(): bool
    {
        auth('api')->logout();
        return true;
    }

    public function refresh(): array
    {
        $token = auth('api')->refresh();
        return $this->tokenResponse($token, auth('api')->user());
    }

    public function me(): ?Authenticatable
    {
        return auth('api')->user();
    }

    protected function tokenResponse(string $token, User $user): array
    {
        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'user'         => $user,
        ];
    }

    protected function validateRegister(array $data): void
    {
        try {
            Validator::make($data, [
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6'],
            ])->validate();
        } catch (ValidationException $e) {
            throw new DomainException($e->getMessage());
        }
    }

    protected function validateLogin(array $data): void
    {
        try {
            Validator::make($data, [
                'email'    => ['required', 'email'],
                'password' => ['required', 'string'],
            ])->validate();
        } catch (ValidationException $e) {
            throw new DomainException($e->getMessage());
        }
    }
}
