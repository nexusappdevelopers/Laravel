<?php

namespace App\Services;

use App\Services\Contracts\BaseServiceInterface;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class BaseService implements BaseServiceInterface
{
    /**
     * The repository instance.
     *
     * @var BaseRepositoryInterface
     */
    protected BaseRepositoryInterface $repository;

    /**
     * The validation rules for creation.
     *
     * @var array
     */
    protected array $createRules = [];

    /**
     * The validation rules for update.
     *
     * @var array
     */
    protected array $updateRules = [];

    /**
     * The validation messages.
     *
     * @var array
     */
    protected array $validationMessages = [];

    /**
     * Create a new service instance.
     *
     * @param BaseRepositoryInterface $repository
     */
    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all records.
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->repository->all($columns);
    }

    /**
     * Get paginated records.
     *
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $columns);
    }

    /**
     * Find a record by ID.
     *
     * @param string|int $id
     * @param array $columns
     * @return Model|null
     */
    public function find($id, array $columns = ['*']): ?Model
    {
        return $this->repository->find($id, $columns);
    }

    /**
     * Find a record by ID or throw an exception.
     *
     * @param string|int $id
     * @param array $columns
     * @return Model
     */
    public function findOrFail($id, array $columns = ['*']): Model
    {
        return $this->repository->findOrFail($id, $columns);
    }

    /**
     * Create a new record.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        $validatedData = $this->validateCreate($data);
        $transformedData = $this->transformData($validatedData);
        
        return $this->repository->create($transformedData);
    }

    /**
     * Update a record.
     *
     * @param string|int $id
     * @param array $data
     * @return Model
     */
    public function update($id, array $data): Model
    {
        $validatedData = $this->validateUpdate($id, $data);
        $transformedData = $this->transformData($validatedData);
        
        return $this->repository->update($id, $transformedData);
    }

    /**
     * Delete a record.
     *
     * @param string|int $id
     * @return bool
     */
    public function delete($id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Search records.
     *
     * @param string $search
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($search)->paginate($perPage);
    }

    /**
     * Filter records.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function filter(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->filter($filters)->paginate($perPage);
    }

    /**
     * Get records with relationships.
     *
     * @param array $relations
     * @return Collection
     */
    public function with(array $relations): Collection
    {
        return $this->repository->with($relations)->all();
    }

    /**
     * Get records with relationships (paginated).
     *
     * @param array $relations
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function withPaginated(array $relations, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->with($relations)->paginate($perPage);
    }

    /**
     * Get statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->repository->count(),
            'created_today' => $this->repository->whereDate('created_at', today())->count(),
            'created_this_week' => $this->repository->whereDate('created_at', '>=', now()->startOfWeek())->count(),
            'created_this_month' => $this->repository->whereMonth('created_at', now()->month)->count(),
        ];
    }

    /**
     * Get export data.
     *
     * @param array $filters
     * @param array $columns
     * @return Collection
     */
    public function getExportData(array $filters = [], array $columns = ['*']): Collection
    {
        $query = $this->repository->newQuery();
        
        if (!empty($filters)) {
            $query->filter($filters);
        }
        
        return $query->all($columns);
    }

    /**
     * Bulk create records.
     *
     * @param array $data
     * @return Collection
     */
    public function bulkCreate(array $data): Collection
    {
        $validatedData = [];
        
        foreach ($data as $item) {
            $validatedData[] = $this->validateCreate($item);
        }
        
        $transformedData = array_map([$this, 'transformData'], $validatedData);
        
        return $this->repository->createMany($transformedData);
    }

    /**
     * Bulk update records.
     *
     * @param array $ids
     * @param array $data
     * @return int
     */
    public function bulkUpdate(array $ids, array $data): int
    {
        $validatedData = $this->validateUpdate(null, $data);
        $transformedData = $this->transformData($validatedData);
        
        return $this->repository->newQuery()
                               ->whereIn($this->repository->getModel()->getKeyName(), $ids)
                               ->update($transformedData);
    }

    /**
     * Bulk delete records.
     *
     * @param array $ids
     * @return int
     */
    public function bulkDelete(array $ids): int
    {
        $count = 0;
        
        foreach ($ids as $id) {
            if ($this->delete($id)) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Restore a soft deleted record.
     *
     * @param string|int $id
     * @return bool
     */
    public function restore($id): bool
    {
        return $this->repository->restore($id);
    }

    /**
     * Force delete a record.
     *
     * @param string|int $id
     * @return bool
     */
    public function forceDelete($id): bool
    {
        return $this->repository->forceDelete($id);
    }

    /**
     * Get trashed records.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function trashed(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->onlyTrashed()->paginate($perPage);
    }

    /**
     * Validate data for creation.
     *
     * @param array $data
     * @return array
     */
    public function validateCreate(array $data): array
    {
        return $this->validate($data, $this->createRules);
    }

    /**
     * Validate data for update.
     *
     * @param string|int|null $id
     * @param array $data
     * @return array
     */
    public function validateUpdate($id, array $data): array
    {
        $rules = $this->updateRules;
        
        // If ID is provided, add unique rules
        if ($id) {
            $model = $this->repository->getModel();
            $rules = $this->addUniqueRules($rules, $id, $model);
        }
        
        return $this->validate($data, $rules);
    }

    /**
     * Transform data for storage.
     *
     * @param array $data
     * @return array
     */
    public function transformData(array $data): array
    {
        // This method should be implemented in child classes
        // to handle specific data transformations
        return $data;
    }

    /**
     * Transform model for response.
     *
     * @param Model $model
     * @return array
     */
    public function transformModel(Model $model): array
    {
        // This method should be implemented in child classes
        // to handle specific model transformations
        return $model->toArray();
    }

    /**
     * Validate data against rules.
     *
     * @param array $data
     * @param array $rules
     * @return array
     */
    protected function validate(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules, $this->validationMessages);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return $validator->validated();
    }

    /**
     * Add unique rules for update validation.
     *
     * @param array $rules
     * @param string|int $id
     * @param Model $model
     * @return array
     */
    protected function addUniqueRules(array $rules, $id, Model $model): array
    {
        foreach ($rules as $field => $rule) {
            if (is_string($rule) && str_contains($rule, 'unique')) {
                $parts = explode(',', $rule);
                $table = $parts[0];
                $column = $parts[1] ?? $field;
                
                $rules[$field] = str_replace(
                    "unique:{$table}",
                    "unique:{$table},{$column},{$id},{$model->getKeyName()}",
                    $rule
                );
            }
        }
        
        return $rules;
    }

    /**
     * Get the repository instance.
     *
     * @return BaseRepositoryInterface
     */
    protected function getRepository(): BaseRepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Set the repository instance.
     *
     * @param BaseRepositoryInterface $repository
     * @return $this
     */
    public function setRepository(BaseRepositoryInterface $repository): self
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * Begin a database transaction.
     *
     * @return void
     */
    protected function beginTransaction(): void
    {
        \DB::beginTransaction();
    }

    /**
     * Commit a database transaction.
     *
     * @return void
     */
    protected function commit(): void
    {
        \DB::commit();
    }

    /**
     * Rollback a database transaction.
     *
     * @return void
     */
    protected function rollback(): void
    {
        \DB::rollBack();
    }

    /**
     * Execute a callback within a database transaction.
     *
     * @param callable $callback
     * @return mixed
     */
    protected function transaction(callable $callback)
    {
        $this->beginTransaction();
        
        try {
            $result = $callback();
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Log an activity.
     *
     * @param string $action
     * @param Model $model
     * @param array $properties
     * @return void
     */
    protected function logActivity(string $action, Model $model, array $properties = []): void
    {
        activity()
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->withProperties($properties)
            ->log($action);
    }

    /**
     * Fire an event.
     *
     * @param object $event
     * @return void
     */
    protected function fireEvent(object $event): void
    {
        event($event);
    }

    /**
     * Dispatch a job.
     *
     * @param object $job
     * @return void
     */
    protected function dispatchJob(object $job): void
    {
        dispatch($job);
    }

    /**
     * Send a notification.
     *
     * @param mixed $notifiable
     * @param object $notification
     * @return void
     */
    protected function sendNotification($notifiable, object $notification): void
    {
        $notifiable->notify($notification);
    }

    /**
     * Cache a value.
     *
     * @param string $key
     * @param mixed $value
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @return void
     */
    protected function cache(string $key, $value, $ttl = null): void
    {
        cache()->put($key, $value, $ttl);
    }

    /**
     * Get a cached value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getCached(string $key, $default = null)
    {
        return cache()->get($key, $default);
    }

    /**
     * Forget a cached value.
     *
     * @param string $key
     * @return void
     */
    protected function forgetCache(string $key): void
    {
        cache()->forget($key);
    }

    /**
     * Clear cache for a specific tag.
     *
     * @param string $tag
     * @return void
     */
    protected function clearCacheTag(string $tag): void
    {
        cache()->tags([$tag])->flush();
    }
}
