<?php

namespace AutoCrud\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SchemaAnalyzer
{
    protected string $table;
    protected string $connection;

    public function __construct(string $table, ?string $connection = null)
    {
        $this->table = $table;
        $this->connection = $connection ?? config('database.default');
    }

    /**
     * Get all columns with their details.
     */
    public function getColumns(): array
    {
        $columns = [];
        $columnNames = Schema::connection($this->connection)->getColumnListing($this->table);

        foreach ($columnNames as $columnName) {
            $columns[$columnName] = $this->getColumnDetails($columnName);
        }

        return $columns;
    }

    /**
     * Get details for a specific column.
     */
    public function getColumnDetails(string $column): array
    {
        $connection = DB::connection($this->connection);
        $doctrine = $connection->getDoctrineColumn($this->table, $column);

        return [
            'name' => $column,
            'type' => $this->mapDoctrineType($doctrine->getType()->getName()),
            'doctrine_type' => $doctrine->getType()->getName(),
            'length' => $doctrine->getLength(),
            'nullable' => !$doctrine->getNotnull(),
            'default' => $doctrine->getDefault(),
            'unsigned' => $doctrine->getUnsigned(),
            'autoincrement' => $doctrine->getAutoincrement(),
            'is_primary' => $this->isPrimaryKey($column),
            'is_foreign' => $this->isForeignKey($column),
            'is_unique' => $this->isUnique($column),
        ];
    }

    /**
     * Map Doctrine types to Laravel types.
     */
    protected function mapDoctrineType(string $doctrineType): string
    {
        $map = [
            'smallint' => 'integer',
            'integer' => 'integer',
            'bigint' => 'bigInteger',
            'decimal' => 'decimal',
            'float' => 'float',
            'string' => 'string',
            'text' => 'text',
            'guid' => 'uuid',
            'binary' => 'binary',
            'blob' => 'binary',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'dateTime',
            'datetimetz' => 'dateTimeTz',
            'time' => 'time',
            'array' => 'json',
            'simple_array' => 'json',
            'json_array' => 'json',
            'json' => 'json',
            'object' => 'json',
        ];

        return $map[$doctrineType] ?? 'string';
    }

    /**
     * Check if column is a primary key.
     */
    protected function isPrimaryKey(string $column): bool
    {
        // Most common convention
        return $column === 'id';
    }

    /**
     * Check if column is a foreign key.
     */
    public function isForeignKey(string $column): bool
    {
        // Convention: ends with _id
        return Str::endsWith($column, '_id');
    }

    /**
     * Check if column has a unique constraint.
     */
    protected function isUnique(string $column): bool
    {
        // This is simplified - in production you'd query the index metadata
        return in_array($column, ['email', 'slug', 'uuid']);
    }

    /**
     * Get fillable columns (exclude id, timestamps, soft deletes).
     */
    public function getFillableColumns(): array
    {
        $exclude = ['id', 'created_at', 'updated_at', 'deleted_at'];

        return array_filter(
            $this->getColumns(),
            fn ($col) => !in_array($col['name'], $exclude) && !$col['autoincrement']
        );
    }

    /**
     * Detect relationships based on foreign key columns.
     */
    public function detectRelationships(): array
    {
        $relationships = [];

        foreach ($this->getColumns() as $column) {
            if ($this->isForeignKey($column['name'])) {
                $relatedModel = $this->guessRelatedModel($column['name']);
                $relationships[] = [
                    'type' => 'belongsTo',
                    'column' => $column['name'],
                    'related_model' => $relatedModel,
                    'method_name' => Str::camel(Str::beforeLast($column['name'], '_id')),
                ];
            }
        }

        return $relationships;
    }

    /**
     * Guess the related model name from a foreign key column.
     */
    protected function guessRelatedModel(string $column): string
    {
        // user_id -> User
        // category_id -> Category
        $name = Str::beforeLast($column, '_id');
        return Str::studly($name);
    }

    /**
     * Get columns that should be cast.
     */
    public function getCastableColumns(): array
    {
        $casts = [];
        $castTypes = [
            'boolean' => 'boolean',
            'json' => 'array',
            'date' => 'date',
            'dateTime' => 'datetime',
            'dateTimeTz' => 'datetime',
            'decimal' => 'decimal:2',
            'float' => 'float',
            'integer' => 'integer',
            'bigInteger' => 'integer',
        ];

        foreach ($this->getColumns() as $column) {
            if (isset($castTypes[$column['type']])) {
                // Skip auto-cast columns
                if (in_array($column['name'], ['created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }
                $casts[$column['name']] = $castTypes[$column['type']];
            }
        }

        return $casts;
    }

    /**
     * Check if table has soft deletes.
     */
    public function hasSoftDeletes(): bool
    {
        return Schema::connection($this->connection)->hasColumn($this->table, 'deleted_at');
    }

    /**
     * Check if table has timestamps.
     */
    public function hasTimestamps(): bool
    {
        return Schema::connection($this->connection)->hasColumn($this->table, 'created_at')
            && Schema::connection($this->connection)->hasColumn($this->table, 'updated_at');
    }

    /**
     * Get date columns.
     */
    public function getDateColumns(): array
    {
        $dateTypes = ['date', 'dateTime', 'dateTimeTz', 'time'];

        return array_filter(
            $this->getColumns(),
            fn ($col) => in_array($col['type'], $dateTypes)
        );
    }
}
