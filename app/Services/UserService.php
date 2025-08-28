<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class UserService
{
    protected UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllUsers()
    {
        try {
            return $this->repository->all();
        } catch (Exception $e) {
            Log::error("Erro ao listar usuários: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception("Não foi possível listar usuários.");
        }
    }

    public function createUser(array $data)
    {
        try {
            return $this->repository->create($data);
        } catch (Exception $e) {
            Log::error("Erro ao criar usuário: {$e->getMessage()}", [
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception("Não foi possível criar o usuário.");
        }
    }

    public function updateUser($id, array $data)
    {
        try {
            return $this->repository->update($id, $data);
        } catch (ModelNotFoundException $e) {
            Log::warning("Tentativa de atualizar usuário inexistente: ID {$id}");
            throw new Exception("Usuário não encontrado para atualização.");
        } catch (Exception $e) {
            Log::error("Erro ao atualizar usuário ID {$id}: {$e->getMessage()}", [
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception("Não foi possível atualizar o usuário.");
        }
    }

    public function deleteUser($id)
    {
        try {
            return $this->repository->delete($id);
        } catch (ModelNotFoundException $e) {
            Log::warning("Tentativa de deletar usuário inexistente: ID {$id}");
            throw new Exception("Usuário não encontrado para exclusão.");
        } catch (Exception $e) {
            Log::error("Erro ao deletar usuário ID {$id}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception("Não foi possível deletar o usuário.");
        }
    }

    public function getUserById($id): ?\Illuminate\Database\Eloquent\Model
    {
        try {
            return $this->repository->show($id);
        } catch (ModelNotFoundException $e) {
            Log::warning("Tentativa de buscar usuário inexistente: ID {$id}");
            throw new Exception("Usuário não encontrado.");
        } catch (Exception $e) {
            Log::error("Erro ao buscar usuário ID {$id}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception("Não foi possível buscar o usuário.");
        }
    }
}
