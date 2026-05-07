<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    /**
     * Get all records.
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Find a record by ID.
     *
     * @param string|int $id
     * @param array $columns
     * @return Model|null
     */
    public function find($id, array $columns = ['*']): ?Model;

    /**
     * Find a record by ID or throw an exception.
     *
     * @param string|int $id
     * @param array $columns
     * @return Model
     */
    public function findOrFail($id, array $columns = ['*']): Model;

    /**
     * Create a new record.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update a record.
     *
     * @param string|int $id
     * @param array $data
     * @return Model
     */
    public function update($id, array $data): Model;

    /**
     * Delete a record.
     *
     * @param string|int $id
     * @return bool
     */
    public function delete($id): bool;

    /**
     * Get paginated results.
     *
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Apply filters to the query.
     *
     * @param array $filters
     * @return $this
     */
    public function filter(array $filters): self;

    /**
     * Apply search to the query.
     *
     * @param string $search
     * @return $this
     */
    public function search(string $search): self;

    /**
     * Apply sorting to the query.
     *
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'asc'): self;

    /**
     * Get the query builder instance.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(): \Illuminate\Database\Eloquent\Builder;

    /**
     * Begin a new query.
     *
     * @return $this
     */
    public function newQuery(): self;

    /**
     * Get the model instance.
     *
     * @return Model
     */
    public function getModel(): Model;

    /**
     * Set the model instance.
     *
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model): self;
}
