<?php

namespace AutoCrud\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait HasCrudOperations
{
    use Filterable, Sortable;

    /**
     * Get paginated results with filtering and sorting.
     *
     * Usage: Model::crud($request)->get()
     */
    public function scopeCrud(Builder $query, Request $request): Builder
    {
        return $query
            ->filter($request->all())
            ->sortFromRequest($request);
    }

    /**
     * Get paginated results with filtering and sorting.
     *
     * Usage: Model::paginatedCrud($request)
     */
    public function scopePaginatedCrud(Builder $query, Request $request): LengthAwarePaginator
    {
        $perPage = $this->getCrudPerPage($request);

        return $query
            ->crud($request)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get the number of items per page from request.
     */
    protected function getCrudPerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', config('auto-crud.pagination.per_page', 15));
        $maxPerPage = config('auto-crud.pagination.max_per_page', 100);

        return min($perPage, $maxPerPage);
    }

    /**
     * Scope to include trashed records when soft deletes is enabled.
     */
    public function scopeWithTrashedIf(Builder $query, bool $condition): Builder
    {
        if ($condition && method_exists($this, 'withTrashed')) {
            return $query->withTrashed();
        }

        return $query;
    }

    /**
     * Scope to only show trashed records.
     */
    public function scopeOnlyTrashedIf(Builder $query, bool $condition): Builder
    {
        if ($condition && method_exists($this, 'onlyTrashed')) {
            return $query->onlyTrashed();
        }

        return $query;
    }

    /**
     * Apply search if search term is provided.
     */
    public function scopeSearchIf(Builder $query, ?string $term, array $fields = []): Builder
    {
        if ($term === null || $term === '') {
            return $query;
        }

        return $query->search($term, $fields);
    }

    /**
     * Get the table columns for this model.
     */
    public static function getTableColumns(): array
    {
        $instance = new static;
        $table = $instance->getTable();

        return $instance->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($table);
    }

    /**
     * Get the fillable columns that exist in the table.
     */
    public function getValidFillable(): array
    {
        $columns = static::getTableColumns();

        return array_intersect($this->getFillable(), $columns);
    }
}
