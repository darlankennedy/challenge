<?php

namespace App\repository;

use App\interface\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Exception;

class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        try {
            return $this->model->all();
        } catch (\Exception $e) {
            Log::error("function all",[
                'file' => $e->getFile(),
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    public function create(array $data)
    {
        try {
            return $this->model->create($data);
        } catch (Exception $e) {
            Log::error("function create",[
                'file' => $e->getFile(),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function update($id, array $data)
    {
        try {
            $record = $this->show($id);
            if (!$record) {
                Log::warning("not found", ['id' => $id]);
                return null;
            }
            $record->fill($data);
            $record->save();
            return $record;
        } catch (Exception $e) {
            Log::error("function update",[
                'file' => $e->getFile(),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function delete($id)
    {
        try {
            $record = $this->show($id);
            if (!$record) {
                Log::warning("not found", ['id' => $id]);
                return false;
            }

            return $record->delete();
        } catch (Exception $e) {
            Log::error("function delete",[
                'file' => $e->getFile(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function show($id)
    {
        try {
            return $this->model->find($id);
        } catch (\Exception $e) {
            Log::error("function find",[
                'file' => $e->getFile(),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
