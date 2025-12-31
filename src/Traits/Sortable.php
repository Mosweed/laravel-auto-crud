<?php

namespace AutoCrud\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Sortable
{
    /**
     * The fields that are sortable.
     * Override in model: protected array $sortable = ['name', 'created_at'];
     * Use '*' for all fields.
     */
    protected function getSortableFields(): array
    {
        if (property_exists($this, 'sortable')) {
            return $this->sortable;
        }

        $config = config('auto-crud.sortable', '*');

        if ($config === '*') {
            return array_merge($this->getFillable(), ['id', 'created_at', 'updated_at']);
        }

        return is_array($config) ? $config : [];
    }

    /**
     * Get default sort configuration.
     */
    protected function getDefaultSort(): array
    {
        if (property_exists($this, 'defaultSort')) {
            return $this->defaultSort;
        }

        return config('auto-crud.default_sort', [
            'field' => 'created_at',
            'direction' => 'desc',
        ]);
    }

    /**
     * Scope to sort the query.
     *
     * Usage:
     * - Model::sort('name', 'asc')->get()
     * - Model::sort(['name', 'created_at'], ['asc', 'desc'])->get()
     * - Model::sort('name')->get() // defaults to 'asc'
     */
    public function scopeSort(Builder $query, string|array|null $field = null, string|array|null $direction = null): Builder
    {
        $sortable = $this->getSortableFields();
        $default = $this->getDefaultSort();

        // Use default if no field provided
        if ($field === null) {
            $field = $default['field'];
            $direction = $default['direction'];
        }

        // Normalize direction
        $direction = $direction ?? 'asc';

        // Handle multiple sort fields
        if (is_array($field)) {
            $directions = is_array($direction) ? $direction : array_fill(0, count($field), $direction);

            foreach ($field as $index => $f) {
                if ($this->isSortable($f, $sortable)) {
                    $dir = $this->normalizeDirection($directions[$index] ?? 'asc');
                    $query->orderBy($f, $dir);
                }
            }

            return $query;
        }

        // Single field sort
        if ($this->isSortable($field, $sortable)) {
            $query->orderBy($field, $this->normalizeDirection($direction));
        }

        return $query;
    }

    /**
     * Scope to apply sorting from request parameters.
     *
     * Usage: Model::sortFromRequest($request)->get()
     *
     * Expects:
     * - ?sort=field&direction=asc
     * - ?sort[]=field1&sort[]=field2&direction[]=asc&direction[]=desc
     */
    public function scopeSortFromRequest(Builder $query, $request): Builder
    {
        $sort = $request->input('sort');
        $direction = $request->input('direction');

        if ($sort === null) {
            $default = $this->getDefaultSort();
            return $query->sort($default['field'], $default['direction']);
        }

        return $query->sort($sort, $direction);
    }

    /**
     * Check if a field is sortable.
     */
    protected function isSortable(string $field, array $sortable): bool
    {
        if (in_array('*', $sortable)) {
            return true;
        }

        return in_array($field, $sortable);
    }

    /**
     * Normalize sort direction.
     */
    protected function normalizeDirection(string $direction): string
    {
        $direction = strtolower($direction);

        return in_array($direction, ['asc', 'desc']) ? $direction : 'asc';
    }
}
