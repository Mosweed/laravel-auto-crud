<?php

namespace AutoCrud\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait Filterable
{
    /**
     * The fields that are filterable.
     * Override in model: protected array $filterable = ['name', 'email'];
     * Use '*' for all fields.
     */
    protected function getFilterableFields(): array
    {
        if (property_exists($this, 'filterable')) {
            return $this->filterable;
        }

        $config = config('auto-crud.filterable', '*');

        if ($config === '*') {
            return $this->getFillable();
        }

        return is_array($config) ? $config : [];
    }

    /**
     * Scope to filter the query based on request parameters.
     *
     * Usage: Model::filter($request->all())->get()
     *
     * Supports:
     * - ?field=value - Exact match
     * - ?field=*value* - LIKE search (wildcards)
     * - ?field_from=x&field_to=y - Range queries
     * - ?field[]=a&field[]=b - IN queries
     * - ?field_not=value - NOT equal
     * - ?field_null=1 - IS NULL
     * - ?field_not_null=1 - IS NOT NULL
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $filterable = $this->getFilterableFields();

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // Handle _from suffix (range start)
            if (Str::endsWith($key, '_from')) {
                $field = Str::beforeLast($key, '_from');
                if ($this->isFilterable($field, $filterable)) {
                    $query->where($field, '>=', $value);
                }
                continue;
            }

            // Handle _to suffix (range end)
            if (Str::endsWith($key, '_to')) {
                $field = Str::beforeLast($key, '_to');
                if ($this->isFilterable($field, $filterable)) {
                    $query->where($field, '<=', $value);
                }
                continue;
            }

            // Handle _not suffix (not equal)
            if (Str::endsWith($key, '_not')) {
                $field = Str::beforeLast($key, '_not');
                if ($this->isFilterable($field, $filterable)) {
                    $query->where($field, '!=', $value);
                }
                continue;
            }

            // Handle _null suffix (is null)
            if (Str::endsWith($key, '_null') && $value) {
                $field = Str::beforeLast($key, '_null');
                if ($this->isFilterable($field, $filterable)) {
                    $query->whereNull($field);
                }
                continue;
            }

            // Handle _not_null suffix (is not null)
            if (Str::endsWith($key, '_not_null') && $value) {
                $field = Str::beforeLast($key, '_not_null');
                if ($this->isFilterable($field, $filterable)) {
                    $query->whereNotNull($field);
                }
                continue;
            }

            // Skip non-filterable fields
            if (!$this->isFilterable($key, $filterable)) {
                continue;
            }

            // Handle array values (IN query)
            if (is_array($value)) {
                $query->whereIn($key, $value);
                continue;
            }

            // Handle wildcard search (LIKE)
            if (Str::contains($value, '*')) {
                $searchValue = str_replace('*', '%', $value);
                $query->where($key, 'LIKE', $searchValue);
                continue;
            }

            // Handle boolean values
            if (in_array(strtolower($value), ['true', 'false', '1', '0'], true)) {
                $query->where($key, filter_var($value, FILTER_VALIDATE_BOOLEAN));
                continue;
            }

            // Exact match
            $query->where($key, $value);
        }

        return $query;
    }

    /**
     * Check if a field is filterable.
     */
    protected function isFilterable(string $field, array $filterable): bool
    {
        if (in_array('*', $filterable)) {
            return true;
        }

        return in_array($field, $filterable);
    }

    /**
     * Scope to search across multiple fields.
     *
     * Usage: Model::search('john', ['name', 'email'])->get()
     */
    public function scopeSearch(Builder $query, string $term, array $fields = []): Builder
    {
        if (empty($fields)) {
            $fields = $this->getFilterableFields();
        }

        return $query->where(function (Builder $q) use ($term, $fields) {
            foreach ($fields as $field) {
                $q->orWhere($field, 'LIKE', "%{$term}%");
            }
        });
    }
}
