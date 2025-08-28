<?php

namespace App\Interface;


interface BaseRepositoryInterface
{
    public function all();
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function show($id, );

}
