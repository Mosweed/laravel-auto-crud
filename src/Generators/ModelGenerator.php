<?php

namespace AutoCrud\Generators;

use Illuminate\Support\Str;

class ModelGenerator extends BaseGenerator
{
    protected array $columns;
    protected array $relationships;

    public function __construct(string $modelName, array $options = [], array $columns = [], array $relationships = [])
    {
        parent::__construct($modelName, $options);
        $this->columns = $columns;
        $this->relationships = $relationships;
    }

    public function generate(): array
    {
        $content = $this->getStubContent('model.stub');
        $content = $this->replacePlaceholders($content, $this->getReplacements());

        $path = config('auto-crud.paths.models') . '/' . $this->modelName . '.php';
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function getReplacements(): array
    {
        return [
            'namespace' => config('auto-crud.namespaces.models'),
            'class' => $this->modelName,
            'fillable' => $this->generateFillable(),
            'casts' => $this->generateCasts(),
            'filterable' => $this->generateFilterable(),
            'sortable' => $this->generateSortable(),
            'relationships' => $this->generateRelationships(),
            'softDeletesImport' => $this->hasSoftDeletes() ? 'use Illuminate\Database\Eloquent\SoftDeletes;' : '',
            'softDeletesTrait' => $this->hasSoftDeletes() ? ', SoftDeletes' : '',
        ];
    }

    protected function generateFillable(): string
    {
        if (empty($this->columns)) {
            return "        // Add your fillable fields here";
        }

        $fillable = array_filter(
            array_keys($this->columns),
            fn ($col) => !in_array($col, ['id', 'created_at', 'updated_at', 'deleted_at'])
        );

        $lines = array_map(fn ($col) => "        '{$col}',", $fillable);

        return implode("\n", $lines);
    }

    protected function generateCasts(): string
    {
        if (empty($this->columns)) {
            return "        // Add your casts here";
        }

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

        foreach ($this->columns as $name => $column) {
            if (isset($castTypes[$column['type']]) && !in_array($name, ['created_at', 'updated_at', 'deleted_at'])) {
                $casts[] = "        '{$name}' => '{$castTypes[$column['type']]}',";
            }
        }

        return empty($casts) ? "        //" : implode("\n", $casts);
    }

    protected function generateFilterable(): string
    {
        if (empty($this->columns)) {
            return "        '*',";
        }

        $filterable = array_filter(
            array_keys($this->columns),
            fn ($col) => !in_array($col, ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'])
        );

        $lines = array_map(fn ($col) => "        '{$col}',", $filterable);

        return implode("\n", $lines);
    }

    protected function generateSortable(): string
    {
        if (empty($this->columns)) {
            return "        '*',";
        }

        $sortable = array_filter(
            array_keys($this->columns),
            fn ($col) => !in_array($col, ['password', 'remember_token'])
        );

        $lines = array_map(fn ($col) => "        '{$col}',", $sortable);

        return implode("\n", $lines);
    }

    protected function generateRelationships(): string
    {
        if (empty($this->relationships)) {
            return '';
        }

        $methods = [];

        foreach ($this->relationships as $rel) {
            $methods[] = $this->generateRelationshipMethod($rel);
        }

        return "\n" . implode("\n", $methods);
    }

    protected function generateRelationshipMethod(array $relationship): string
    {
        $method = $relationship['method_name'];
        $related = $relationship['related_model'];
        $type = $relationship['type'];

        switch ($type) {
            case 'belongsTo':
                $returnType = 'BelongsTo';
                $description = "Get the {$method} that owns the {$this->modelName}.";
                $body = "return \$this->belongsTo({$related}::class);";
                break;

            case 'hasMany':
                $returnType = 'HasMany';
                $description = "Get the {$method} for the {$this->modelName}.";
                $body = "return \$this->hasMany({$related}::class);";
                break;

            case 'hasOne':
                $returnType = 'HasOne';
                $description = "Get the {$method} associated with the {$this->modelName}.";
                $body = "return \$this->hasOne({$related}::class);";
                break;

            case 'belongsToMany':
                $returnType = 'BelongsToMany';
                $description = "The {$method} that belong to the {$this->modelName}.";
                $body = "return \$this->belongsToMany({$related}::class);";
                break;

            case 'morphMany':
                $returnType = 'MorphMany';
                $description = "Get all of the {$method} for the {$this->modelName}.";
                $body = "return \$this->morphMany({$related}::class, '" . Str::camel($this->modelName) . "able');";
                break;

            case 'morphTo':
                $returnType = 'MorphTo';
                $description = "Get the parent {$method} model.";
                $body = "return \$this->morphTo();";
                break;

            default:
                $returnType = 'BelongsTo';
                $description = "Get the {$method} relationship.";
                $body = "return \$this->belongsTo({$related}::class);";
        }

        return <<<PHP

    /**
     * {$description}
     */
    public function {$method}(): \Illuminate\Database\Eloquent\Relations\\{$returnType}
    {
        {$body}
    }
PHP;
    }
}
