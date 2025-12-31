<?php

namespace AutoCrud\Generators;

use Illuminate\Support\Str;

class FactoryGenerator extends BaseGenerator
{
    protected array $columns;

    public function __construct(string $modelName, array $options = [], array $columns = [])
    {
        parent::__construct($modelName, $options);
        $this->columns = $columns;
    }

    public function generate(): array
    {
        $content = $this->getStubContent('factory.stub');
        $content = $this->replacePlaceholders($content, $this->getReplacements());

        $path = config('auto-crud.paths.factories') . '/' . $this->modelName . 'Factory.php';
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function getReplacements(): array
    {
        return [
            'model' => $this->modelName,
            'modelNamespace' => config('auto-crud.namespaces.models'),
            'definitions' => $this->generateDefinitions(),
            'states' => $this->hasSoftDeletes() ? $this->generateSoftDeleteState() : '',
        ];
    }

    protected function generateDefinitions(): string
    {
        if (empty($this->columns)) {
            return $this->getDefaultDefinitions();
        }

        $definitions = [];

        foreach ($this->columns as $name => $column) {
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $faker = $this->getFakerMethod($name, $column);
            $definitions[] = "            '{$name}' => {$faker},";
        }

        return implode("\n", $definitions);
    }

    protected function getDefaultDefinitions(): string
    {
        return <<<PHP
            'name' => fake()->name(),
            // Add more factory definitions here
PHP;
    }

    protected function getFakerMethod(string $name, array $column): string
    {
        // Check for common patterns in name
        $nameLower = strtolower($name);

        if (Str::contains($nameLower, 'email')) {
            return "fake()->unique()->safeEmail()";
        }

        if (Str::contains($nameLower, 'name')) {
            if (Str::contains($nameLower, ['first', 'given'])) {
                return "fake()->firstName()";
            }
            if (Str::contains($nameLower, ['last', 'family', 'surname'])) {
                return "fake()->lastName()";
            }
            if (Str::contains($nameLower, ['full', 'display'])) {
                return "fake()->name()";
            }
            if (Str::contains($nameLower, ['user', 'nick'])) {
                return "fake()->userName()";
            }
            if (Str::contains($nameLower, 'company')) {
                return "fake()->company()";
            }
            return "fake()->name()";
        }

        if (Str::contains($nameLower, 'phone')) {
            return "fake()->phoneNumber()";
        }

        if (Str::contains($nameLower, 'address')) {
            return "fake()->address()";
        }

        if (Str::contains($nameLower, 'city')) {
            return "fake()->city()";
        }

        if (Str::contains($nameLower, 'country')) {
            return "fake()->country()";
        }

        if (Str::contains($nameLower, ['zip', 'postal'])) {
            return "fake()->postcode()";
        }

        if (Str::contains($nameLower, 'url')) {
            return "fake()->url()";
        }

        if (Str::contains($nameLower, 'slug')) {
            return "fake()->slug()";
        }

        if (Str::contains($nameLower, 'title')) {
            return "fake()->sentence(3)";
        }

        if (Str::contains($nameLower, ['description', 'body', 'content', 'bio'])) {
            return "fake()->paragraphs(3, true)";
        }

        if (Str::contains($nameLower, 'password')) {
            return "bcrypt('password')";
        }

        if (Str::contains($nameLower, 'token')) {
            return "Str::random(60)";
        }

        if (Str::contains($nameLower, 'uuid')) {
            return "fake()->uuid()";
        }

        if (Str::contains($nameLower, 'color')) {
            return "fake()->hexColor()";
        }

        if (Str::contains($nameLower, ['image', 'avatar', 'photo'])) {
            return "fake()->imageUrl()";
        }

        if (Str::contains($nameLower, ['amount', 'price', 'cost', 'total'])) {
            return "fake()->randomFloat(2, 0, 1000)";
        }

        if (Str::contains($nameLower, ['quantity', 'count', 'number'])) {
            return "fake()->numberBetween(1, 100)";
        }

        if ($column['is_foreign'] ?? false) {
            $relatedModel = Str::studly(Str::beforeLast($name, '_id'));
            return "\\App\\Models\\{$relatedModel}::factory()";
        }

        // Fall back to type-based
        return $this->getFakerMethodByType($column['type'], $column);
    }

    protected function getFakerMethodByType(string $type, array $column): string
    {
        switch ($type) {
            case 'string':
                $length = $column['length'] ?? 255;
                if ($length <= 50) {
                    return "fake()->words(3, true)";
                }
                return "fake()->sentence()";

            case 'text':
                return "fake()->paragraphs(3, true)";

            case 'integer':
            case 'bigInteger':
                if ($column['unsigned'] ?? false) {
                    return "fake()->numberBetween(1, 1000)";
                }
                return "fake()->numberBetween(-1000, 1000)";

            case 'decimal':
            case 'float':
                return "fake()->randomFloat(2, 0, 1000)";

            case 'boolean':
                return "fake()->boolean()";

            case 'date':
                return "fake()->date()";

            case 'dateTime':
            case 'dateTimeTz':
                return "fake()->dateTime()";

            case 'time':
                return "fake()->time()";

            case 'json':
                return "[]";

            case 'uuid':
                return "fake()->uuid()";

            default:
                return "fake()->word()";
        }
    }

    protected function generateSoftDeleteState(): string
    {
        return <<<PHP

    /**
     * Indicate that the model is soft deleted.
     */
    public function trashed(): static
    {
        return \$this->state(fn (array \$attributes) => [
            'deleted_at' => now(),
        ]);
    }
PHP;
    }
}
