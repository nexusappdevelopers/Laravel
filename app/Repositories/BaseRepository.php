<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * The model instance.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * The query builder instance.
     *
     * @var Builder
     */
    protected Builder $query;

    /**
     * Create a new repository instance.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->newQuery();
    }

    /**
     * Get all records.
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->query->get($columns);
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
        return $this->query->find($id, $columns);
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
        return $this->query->findOrFail($id, $columns);
    }

    /**
     * Create a new record.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
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
        $model = $this->findOrFail($id);
        $model->update($data);
        return $model;
    }

    /**
     * Delete a record.
     *
     * @param string|int $id
     * @return bool
     */
    public function delete($id): bool
    {
        $model = $this->findOrFail($id);
        return $model->delete();
    }

    /**
     * Get paginated results.
     *
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->query->paginate($perPage, $columns);
    }

    /**
     * Apply filters to the query.
     *
     * @param array $filters
     * @return $this
     */
    public function filter(array $filters): self
    {
        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                $this->applyFilter($key, $value);
            }
        }

        return $this;
    }

    /**
     * Apply search to the query.
     *
     * @param string $search
     * @return $this
     */
    public function search(string $search): self
    {
        if (!empty($search)) {
            $this->applySearch($search);
        }

        return $this;
    }

    /**
     * Apply sorting to the query.
     *
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * Get the query builder instance.
     *
     * @return Builder
     */
    public function query(): Builder
    {
        return $this->query;
    }

    /**
     * Begin a new query.
     *
     * @return $this
     */
    public function newQuery(): self
    {
        $this->query = $this->model->newQuery();
        return $this;
    }

    /**
     * Get the model instance.
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Set the model instance.
     *
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;
        $this->newQuery();
        return $this;
    }

    /**
     * Apply a filter to the query.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function applyFilter(string $key, $value): void
    {
        // This method should be implemented in child classes
        // to handle specific filtering logic
    }

    /**
     * Apply search to the query.
     *
     * @param string $search
     * @return void
     */
    protected function applySearch(string $search): void
    {
        // This method should be implemented in child classes
        // to handle specific search logic
    }

    /**
     * Apply relationships to the query.
     *
     * @param array $relations
     * @return $this
     */
    public function with(array $relations): self
    {
        $this->query->with($relations);
        return $this;
    }

    /**
     * Apply count relationships to the query.
     *
     * @param array $relations
     * @return $this
     */
    public function withCount(array $relations): self
    {
        $this->query->withCount($relations);
        return $this;
    }

    /**
     * Apply where clause to the query.
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function where(string $column, $operator = null, $value = null): self
    {
        $this->query->where($column, $operator, $value);
        return $this;
    }

    /**
     * Apply whereIn clause to the query.
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereIn(string $column, array $values): self
    {
        $this->query->whereIn($column, $values);
        return $this;
    }

    /**
     * Apply whereNotIn clause to the query.
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereNotIn(string $column, array $values): self
    {
        $this->query->whereNotIn($column, $values);
        return $this;
    }

    /**
     * Apply whereBetween clause to the query.
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereBetween(string $column, array $values): self
    {
        $this->query->whereBetween($column, $values);
        return $this;
    }

    /**
     * Apply whereDate clause to the query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function whereDate(string $column, string $operator, $value): self
    {
        $this->query->whereDate($column, $operator, $value);
        return $this;
    }

    /**
     * Apply whereMonth clause to the query.
     *
     * @param string $column
     * @param mixed $value
     * @return $this
     */
    public function whereMonth(string $column, $value): self
    {
        $this->query->whereMonth($column, $value);
        return $this;
    }

    /**
     * Apply whereYear clause to the query.
     *
     * @param string $column
     * @param mixed $value
     * @return $this
     */
    public function whereYear(string $column, $value): self
    {
        $this->query->whereYear($column, $value);
        return $this;
    }

    /**
     * Apply limit to the query.
     *
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * Apply offset to the query.
     *
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset): self
    {
        $this->query->offset($offset);
        return $this;
    }

    /**
     * Get the first record.
     *
     * @param array $columns
     * @return Model|null
     */
    public function first(array $columns = ['*']): ?Model
    {
        return $this->query->first($columns);
    }

    /**
     * Get the first record or throw an exception.
     *
     * @param array $columns
     * @return Model
     */
    public function firstOrFail(array $columns = ['*']): Model
    {
        return $this->query->firstOrFail($columns);
    }

    /**
     * Get the count of records.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->query->count();
    }

    /**
     * Check if a record exists.
     *
     * @param string|int $id
     * @return bool
     */
    public function exists($id): bool
    {
        return $this->query->where($this->model->getKeyName(), $id)->exists();
    }

    /**
     * Create multiple records.
     *
     * @param array $data
     * @return Collection
     */
    public function createMany(array $data): Collection
    {
        return $this->model->insert($data);
    }

    /**
     * Update or create a record.
     *
     * @param array $attributes
     * @param array $values
     * @return Model
     */
    public function updateOrCreate(array $attributes, array $values): Model
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Find a record by attributes or create a new one.
     *
     * @param array $attributes
     * @param array $values
     * @return Model
     */
    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->firstOrCreate($attributes, $values);
    }

    /**
     * Restore a soft deleted record.
     *
     * @param string|int $id
     * @return bool
     */
    public function restore($id): bool
    {
        $model = $this->query->withTrashed()->findOrFail($id);
        return $model->restore();
    }

    /**
     * Force delete a record.
     *
     * @param string|int $id
     * @return bool
     */
    public function forceDelete($id): bool
    {
        $model = $this->query->withTrashed()->findOrFail($id);
        return $model->forceDelete();
    }

    /**
     * Get only trashed records.
     *
     * @return $this
     */
    public function onlyTrashed(): self
    {
        $this->query->onlyTrashed();
        return $this;
    }

    /**
     * Get trashed records.
     *
     * @return $this
     */
    public function withTrashed(): self
    {
        $this->query->withTrashed();
        return $this;
    }
}
