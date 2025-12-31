<?php

namespace AutoCrud\Generators;

use Illuminate\Support\Str;

class MigrationGenerator extends BaseGenerator
{
    protected array $columns;

    public function __construct(string $modelName, array $options = [], array $columns = [])
    {
        parent::__construct($modelName, $options);
        $this->columns = $columns;
    }

    public function generate(): array
    {
        $content = $this->getStubContent('migration.stub');
        $content = $this->replacePlaceholders($content, $this->getReplacements());

        $timestamp = date('Y_m_d_His');
        $table = $this->getTableName();
        $filename = "{$timestamp}_create_{$table}_table.php";

        $path = config('auto-crud.paths.migrations') . '/' . $filename;
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function getReplacements(): array
    {
        return [
            'table' => $this->getTableName(),
            'columns' => $this->generateColumns(),
            'softDeletes' => $this->hasSoftDeletes() ? '            $table->softDeletes();' : '',
        ];
    }

    protected function generateColumns(): string
    {
        if (empty($this->columns)) {
            return $this->getDefaultColumns();
        }

        $lines = [];

        foreach ($this->columns as $name => $column) {
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $lines[] = $this->generateColumnDefinition($name, $column);
        }

        return implode("\n", $lines);
    }

    protected function getDefaultColumns(): string
    {
        return <<<PHP
            \$table->string('name');
            // Add more columns here
PHP;
    }

    protected function generateColumnDefinition(string $name, array $column): string
    {
        $type = $column['type'];
        $nullable = $column['nullable'] ? '->nullable()' : '';
        $default = $column['default'] !== null ? "->default('{$column['default']}')" : '';
        $unique = ($column['is_unique'] ?? false) ? '->unique()' : '';

        // Handle foreign keys
        if ($column['is_foreign'] ?? false) {
            $relatedTable = Str::plural(Str::beforeLast($name, '_id'));
            return "            \$table->foreignId('{$name}'){$nullable}->constrained('{$relatedTable}')->cascadeOnDelete();";
        }

        // Map types
        $typeMap = [
            'string' => 'string',
            'text' => 'text',
            'integer' => 'integer',
            'bigInteger' => 'bigInteger',
            'decimal' => 'decimal',
            'float' => 'float',
            'boolean' => 'boolean',
            'date' => 'date',
            'dateTime' => 'dateTime',
            'dateTimeTz' => 'dateTimeTz',
            'time' => 'time',
            'json' => 'json',
            'uuid' => 'uuid',
            'binary' => 'binary',
        ];

        $laravelType = $typeMap[$type] ?? 'string';

        $lengthTypes = ['string'];
        $length = in_array($laravelType, $lengthTypes) && $column['length']
            ? ", {$column['length']}"
            : '';

        return "            \$table->{$laravelType}('{$name}'{$length}){$nullable}{$default}{$unique};";
    }
}
