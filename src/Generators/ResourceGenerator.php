<?php

namespace AutoCrud\Generators;

use Illuminate\Support\Str;

class ResourceGenerator extends BaseGenerator
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
        $content = $this->getStubContent('resource.stub');
        $content = $this->replacePlaceholders($content, $this->getReplacements());

        $path = config('auto-crud.paths.resources') . '/' . $this->modelName . 'Resource.php';
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function getReplacements(): array
    {
        return [
            'namespace' => config('auto-crud.namespaces.resources'),
            'model' => $this->modelName,
            'attributes' => $this->generateAttributes(),
            'softDeleteAttribute' => $this->hasSoftDeletes() ? $this->generateSoftDeleteAttribute() : '',
            'relationships' => $this->generateRelationships(),
        ];
    }

    protected function generateAttributes(): string
    {
        if (empty($this->columns)) {
            return "            'name' => \$this->name,";
        }

        $attributes = [];

        foreach ($this->columns as $name => $column) {
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'])) {
                continue;
            }

            $value = $this->formatValue($name, $column);
            $attributes[] = "            '{$name}' => {$value},";
        }

        return implode("\n", $attributes);
    }

    protected function formatValue(string $name, array $column): string
    {
        switch ($column['type']) {
            case 'date':
                return "\$this->{$name}?->toDateString()";

            case 'dateTime':
            case 'dateTimeTz':
                return "\$this->{$name}?->toISOString()";

            case 'boolean':
                return "(bool) \$this->{$name}";

            default:
                return "\$this->{$name}";
        }
    }

    protected function generateSoftDeleteAttribute(): string
    {
        return "            'deleted_at' => \$this->deleted_at?->toISOString(),";
    }

    protected function generateRelationships(): string
    {
        if (empty($this->relationships)) {
            return '';
        }

        $relations = [];

        foreach ($this->relationships as $rel) {
            $method = $rel['method_name'];
            $relatedModel = $rel['related_model'];

            $relations[] = "            '{$method}' => new {$relatedModel}Resource(\$this->whenLoaded('{$method}')),";
        }

        return implode("\n", $relations);
    }
}
