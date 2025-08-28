<?php

namespace App\Repositories;

use App\Interface\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Collection;

class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
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

    public function create(array $data): ?Model
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

    public function update($id, array $data): ?Model
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

    public function delete($id): bool
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

    public function show($id): ?Model
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
