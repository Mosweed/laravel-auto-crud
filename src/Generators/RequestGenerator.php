<?php

namespace AutoCrud\Generators;

use AutoCrud\Services\SchemaAnalyzer;
use AutoCrud\Services\ValidationRuleGenerator;
use Illuminate\Support\Str;

class RequestGenerator extends BaseGenerator
{
    protected array $columns;
    protected ?ValidationRuleGenerator $ruleGenerator;

    public function __construct(string $modelName, array $options = [], array $columns = [])
    {
        parent::__construct($modelName, $options);
        $this->columns = $columns;
        $this->ruleGenerator = null;
    }

    public function generate(): array
    {
        $results = [];

        $results['store'] = $this->generateStoreRequest();
        $results['update'] = $this->generateUpdateRequest();

        return $results;
    }

    protected function generateStoreRequest(): array
    {
        $content = $this->getStubContent('request.store.stub');
        $content = $this->replacePlaceholders($content, $this->getStoreReplacements());

        $directory = config('auto-crud.paths.requests') . '/' . $this->modelName;
        $path = $directory . '/Store' . $this->modelName . 'Request.php';
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function generateUpdateRequest(): array
    {
        $content = $this->getStubContent('request.update.stub');
        $content = $this->replacePlaceholders($content, $this->getUpdateReplacements());

        $directory = config('auto-crud.paths.requests') . '/' . $this->modelName;
        $path = $directory . '/Update' . $this->modelName . 'Request.php';
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function getStoreReplacements(): array
    {
        return [
            'namespace' => config('auto-crud.namespaces.requests'),
            'model' => $this->modelName,
            'rules' => $this->generateStoreRules(),
            'attributes' => $this->generateAttributes(),
        ];
    }

    protected function getUpdateReplacements(): array
    {
        return [
            'namespace' => config('auto-crud.namespaces.requests'),
            'model' => $this->modelName,
            'rules' => $this->generateUpdateRules(),
            'attributes' => $this->generateAttributes(),
        ];
    }

    protected function generateStoreRules(): string
    {
        if (empty($this->columns)) {
            return "            // Add your validation rules here";
        }

        $rules = [];

        foreach ($this->columns as $name => $column) {
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $fieldRules = $this->generateRulesForColumn($column, false);
            $rules[] = "            '{$name}' => [" . implode(', ', array_map(fn ($r) => "'{$r}'", $fieldRules)) . "],";
        }

        return implode("\n", $rules);
    }

    protected function generateUpdateRules(): string
    {
        if (empty($this->columns)) {
            return "            // Add your validation rules here";
        }

        $rules = [];

        foreach ($this->columns as $name => $column) {
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $fieldRules = $this->generateRulesForColumn($column, true);
            $rules[] = "            '{$name}' => [" . implode(', ', array_map(fn ($r) => "'{$r}'", $fieldRules)) . "],";
        }

        return implode("\n", $rules);
    }

    protected function generateRulesForColumn(array $column, bool $isUpdate): array
    {
        $rules = [];

        // Required or nullable
        if (!$column['nullable'] && $column['default'] === null) {
            $rules[] = $isUpdate ? 'sometimes' : 'required';
        } else {
            $rules[] = 'nullable';
        }

        // Type-based rules
        switch ($column['type']) {
            case 'string':
                $rules[] = 'string';
                if ($column['length']) {
                    $rules[] = "max:{$column['length']}";
                }
                if (Str::contains($column['name'], 'email')) {
                    $rules[] = 'email';
                }
                break;

            case 'text':
                $rules[] = 'string';
                break;

            case 'integer':
            case 'bigInteger':
                $rules[] = 'integer';
                break;

            case 'decimal':
            case 'float':
                $rules[] = 'numeric';
                break;

            case 'boolean':
                $rules[] = 'boolean';
                break;

            case 'date':
            case 'dateTime':
            case 'dateTimeTz':
                $rules[] = 'date';
                break;

            case 'json':
                $rules[] = 'array';
                break;

            case 'uuid':
                $rules[] = 'uuid';
                break;
        }

        // Foreign key
        if ($column['is_foreign'] ?? false) {
            $relatedTable = Str::plural(Str::beforeLast($column['name'], '_id'));
            $rules[] = "exists:{$relatedTable},id";
        }

        // Unique
        if ($column['is_unique'] ?? false) {
            $table = $this->getTableName();
            if ($isUpdate) {
                $rules[] = "unique:{$table},{$column['name']},\" . \$this->route('" . $this->getModelVariable() . "')->id";
            } else {
                $rules[] = "unique:{$table},{$column['name']}";
            }
        }

        return $rules;
    }

    protected function generateAttributes(): string
    {
        if (empty($this->columns)) {
            return "            // Add your attribute names here";
        }

        $attributes = [];

        foreach ($this->columns as $name => $column) {
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $label = $this->humanize($name);
            $attributes[] = "            '{$name}' => '{$label}',";
        }

        return implode("\n", $attributes);
    }

    protected function humanize(string $name): string
    {
        return Str::title(str_replace(['_', '-'], ' ', $name));
    }
}
