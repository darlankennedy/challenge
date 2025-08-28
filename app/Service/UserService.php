<?php

namespace App\Service;

use App\repository\UserRepository;

class UserService
{
    protected UserRepository $repository;
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllUsers()
    {
        return $this->repository->all();
    }

    public function createUser(array $data)
    {
        return $this->repository->create($data);
    }

    public function updateUser($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function deleteUser($id)
    {
        return $this->repository->delete($id);
    }

    public function getUserById($id)
    {
        return $this->repository->show($id);
    }

}
