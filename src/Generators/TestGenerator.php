<?php

namespace AutoCrud\Generators;

use Illuminate\Support\Str;

class TestGenerator extends BaseGenerator
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
        $results = [];
        $type = $this->options['type'] ?? 'both';

        // Detect testing framework
        $framework = $this->detectTestingFramework();

        // Generate feature tests
        if (in_array($type, ['web', 'both'])) {
            $results['feature'] = $this->generateFeatureTest($framework);
        }

        if (in_array($type, ['api', 'both'])) {
            $results['api'] = $this->generateApiTest($framework);
        }

        // Generate unit test for model
        $results['unit'] = $this->generateUnitTest($framework);

        return $results;
    }

    /**
     * Detect which testing framework is installed.
     */
    protected function detectTestingFramework(): string
    {
        $composerPath = base_path('composer.json');

        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            $devDeps = $composer['require-dev'] ?? [];

            if (isset($devDeps['pestphp/pest']) || isset($devDeps['pestphp/pest-plugin-laravel'])) {
                return 'pest';
            }
        }

        return 'phpunit';
    }

    protected function generateFeatureTest(string $framework): array
    {
        $stubName = $framework === 'pest' ? 'test.feature.pest.stub' : 'test.feature.phpunit.stub';
        $content = $this->getStubContent($stubName);
        $content = $this->replacePlaceholders($content, $this->getReplacements());

        $path = base_path('tests/Feature/' . $this->modelName . 'Test.php');
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function generateApiTest(string $framework): array
    {
        $stubName = $framework === 'pest' ? 'test.api.pest.stub' : 'test.api.phpunit.stub';
        $content = $this->getStubContent($stubName);
        $content = $this->replacePlaceholders($content, $this->getReplacements());

        $path = base_path('tests/Feature/Api/' . $this->modelName . 'ApiTest.php');
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function generateUnitTest(string $framework): array
    {
        $stubName = $framework === 'pest' ? 'test.unit.pest.stub' : 'test.unit.phpunit.stub';
        $content = $this->getStubContent($stubName);
        $content = $this->replacePlaceholders($content, $this->getReplacements());

        $path = base_path('tests/Unit/' . $this->modelName . 'Test.php');
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function getReplacements(): array
    {
        return array_merge($this->getCommonReplacements(), [
            'modelNamespace' => config('auto-crud.namespaces.models'),
            'factoryData' => $this->generateFactoryData(),
            'updateData' => $this->generateUpdateData(),
            'requiredFields' => $this->generateRequiredFields(),
            'relationships' => $this->generateRelationshipTests(),
            'softDeleteTests' => $this->hasSoftDeletes() ? $this->generateSoftDeleteTests() : '',
        ]);
    }

    protected function generateFactoryData(): string
    {
        if (empty($this->columns)) {
            return "'name' => 'Test " . $this->modelName . "',";
        }

        $lines = [];
        foreach ($this->columns as $name => $column) {
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $value = $this->getTestValueForType($name, $column);
            $lines[] = "        '{$name}' => {$value},";
        }

        return implode("\n", $lines);
    }

    protected function generateUpdateData(): string
    {
        if (empty($this->columns)) {
            return "'name' => 'Updated " . $this->modelName . "',";
        }

        $lines = [];
        foreach ($this->columns as $name => $column) {
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $value = $this->getUpdatedTestValueForType($name, $column);
            $lines[] = "        '{$name}' => {$value},";
        }

        return implode("\n", $lines);
    }

    protected function getTestValueForType(string $name, array $column): string
    {
        $type = $column['type'] ?? 'string';

        // Handle foreign keys
        if ($column['is_foreign'] ?? false) {
            $relatedModel = Str::studly(Str::beforeLast($name, '_id'));
            return "\\App\\Models\\{$relatedModel}::factory()->create()->id";
        }

        return match ($type) {
            'string' => "'Test " . Str::title(str_replace('_', ' ', $name)) . "'",
            'text' => "'This is test content for {$name}.'",
            'integer', 'bigInteger' => '1',
            'decimal', 'float' => '99.99',
            'boolean' => 'true',
            'date' => "now()->format('Y-m-d')",
            'dateTime', 'dateTimeTz' => "now()->format('Y-m-d H:i:s')",
            'json' => "['key' => 'value']",
            default => "'test'",
        };
    }

    protected function getUpdatedTestValueForType(string $name, array $column): string
    {
        $type = $column['type'] ?? 'string';

        // Keep foreign keys the same in update
        if ($column['is_foreign'] ?? false) {
            $relatedModel = Str::studly(Str::beforeLast($name, '_id'));
            return "\${$this->getModelVariable()}->{$name}";
        }

        return match ($type) {
            'string' => "'Updated " . Str::title(str_replace('_', ' ', $name)) . "'",
            'text' => "'This is updated content for {$name}.'",
            'integer', 'bigInteger' => '2',
            'decimal', 'float' => '199.99',
            'boolean' => 'false',
            'date' => "now()->addDay()->format('Y-m-d')",
            'dateTime', 'dateTimeTz' => "now()->addDay()->format('Y-m-d H:i:s')",
            'json' => "['updated' => 'data']",
            default => "'updated'",
        };
    }

    protected function generateRequiredFields(): string
    {
        $required = [];

        foreach ($this->columns as $name => $column) {
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            if (!($column['nullable'] ?? false)) {
                $required[] = "'{$name}'";
            }
        }

        return implode(', ', $required);
    }

    protected function generateRelationshipTests(): string
    {
        if (empty($this->relationships)) {
            return '';
        }

        $tests = [];
        foreach ($this->relationships as $rel) {
            $methodName = $rel['method_name'];
            $tests[] = "    expect(\$model->{$methodName})->not->toBeNull();";
        }

        return implode("\n", $tests);
    }

    protected function generateSoftDeleteTests(): string
    {
        $model = $this->modelName;
        $variable = $this->getModelVariable();
        $route = $this->getRouteName();

        return <<<PHP

it('can soft delete a {$variable}', function () {
    \${$variable} = {$model}::factory()->create();

    \$this->delete(route('{$route}.destroy', \${$variable}))
        ->assertRedirect(route('{$route}.index'));

    \$this->assertSoftDeleted('{$this->getTableName()}', ['id' => \${$variable}->id]);
});

it('can restore a soft deleted {$variable}', function () {
    \${$variable} = {$model}::factory()->create();
    \${$variable}->delete();

    \$this->post(route('{$route}.restore', \${$variable}->id))
        ->assertRedirect(route('{$route}.index'));

    \$this->assertDatabaseHas('{$this->getTableName()}', [
        'id' => \${$variable}->id,
        'deleted_at' => null,
    ]);
});

it('can force delete a {$variable}', function () {
    \${$variable} = {$model}::factory()->create();
    \${$variable}->delete();

    \$this->delete(route('{$route}.force-delete', \${$variable}->id))
        ->assertRedirect(route('{$route}.index'));

    \$this->assertDatabaseMissing('{$this->getTableName()}', ['id' => \${$variable}->id]);
});
PHP;
    }
}
