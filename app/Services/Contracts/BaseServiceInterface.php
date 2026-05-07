<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseServiceInterface
{
    /**
     * Get all records.
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Get paginated records.
     *
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

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
     * Search records.
     *
     * @param string $search
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $search, int $perPage = 15): LengthAwarePaginator;

    /**
     * Filter records.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function filter(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get records with relationships.
     *
     * @param array $relations
     * @return Collection
     */
    public function with(array $relations): Collection;

    /**
     * Get records with relationships (paginated).
     *
     * @param array $relations
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function withPaginated(array $relations, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get statistics.
     *
     * @return array
     */
    public function getStatistics(): array;

    /**
     * Get export data.
     *
     * @param array $filters
     * @param array $columns
     * @return Collection
     */
    public function getExportData(array $filters = [], array $columns = ['*']): Collection;

    /**
     * Bulk create records.
     *
     * @param array $data
     * @return Collection
     */
    public function bulkCreate(array $data): Collection;

    /**
     * Bulk update records.
     *
     * @param array $ids
     * @param array $data
     * @return int
     */
    public function bulkUpdate(array $ids, array $data): int;

    /**
     * Bulk delete records.
     *
     * @param array $ids
     * @return int
     */
    public function bulkDelete(array $ids): int;

    /**
     * Restore a soft deleted record.
     *
     * @param string|int $id
     * @return bool
     */
    public function restore($id): bool;

    /**
     * Force delete a record.
     *
     * @param string|int $id
     * @return bool
     */
    public function forceDelete($id): bool;

    /**
     * Get trashed records.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function trashed(int $perPage = 15): LengthAwarePaginator;

    /**
     * Validate data for creation.
     *
     * @param array $data
     * @return array
     */
    public function validateCreate(array $data): array;

    /**
     * Validate data for update.
     *
     * @param string|int $id
     * @param array $data
     * @return array
     */
    public function validateUpdate($id, array $data): array;

    /**
     * Transform data for storage.
     *
     * @param array $data
     * @return array
     */
    public function transformData(array $data): array;

    /**
     * Transform model for response.
     *
     * @param Model $model
     * @return array
     */
    public function transformModel(Model $model): array;
}
