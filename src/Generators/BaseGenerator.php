<?php

namespace AutoCrud\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

abstract class BaseGenerator
{
    protected Filesystem $files;
    protected string $modelName;
    protected array $options;

    public function __construct(string $modelName, array $options = [])
    {
        $this->files = new Filesystem();
        $this->modelName = $modelName;
        $this->options = $options;
    }

    /**
     * Generate the file(s).
     */
    abstract public function generate(): array;

    /**
     * Get the stub file path.
     */
    protected function getStub(string $name): string
    {
        $customPath = config('auto-crud.stub_path') . '/' . $name;

        if ($this->files->exists($customPath)) {
            return $customPath;
        }

        return __DIR__ . '/../stubs/' . $name;
    }

    /**
     * Get the stub content.
     */
    protected function getStubContent(string $name): string
    {
        return $this->files->get($this->getStub($name));
    }

    /**
     * Replace placeholders in stub content.
     */
    protected function replacePlaceholders(string $content, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $content = str_replace('{{ ' . $key . ' }}', $value, $content);
        }

        return $content;
    }

    /**
     * Create a file.
     */
    protected function createFile(string $path, string $content): bool
    {
        $directory = dirname($path);

        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        if ($this->files->exists($path) && !($this->options['force'] ?? false)) {
            return false;
        }

        return (bool) $this->files->put($path, $content);
    }

    /**
     * Get the model name in various formats.
     */
    protected function getModelVariable(): string
    {
        return Str::camel($this->modelName);
    }

    protected function getModelVariablePlural(): string
    {
        return Str::camel(Str::plural($this->modelName));
    }

    protected function getModelPlural(): string
    {
        return Str::plural($this->modelName);
    }

    protected function getModelPluralLower(): string
    {
        return strtolower(Str::plural($this->modelName));
    }

    protected function getModelKebab(): string
    {
        return Str::kebab($this->modelName);
    }

    protected function getModelKebabPlural(): string
    {
        return Str::kebab(Str::plural($this->modelName));
    }

    protected function getTableName(): string
    {
        return Str::snake(Str::plural($this->modelName));
    }

    protected function getRouteName(): string
    {
        return Str::kebab(Str::plural($this->modelName));
    }

    protected function getViewPath(): string
    {
        return Str::kebab(Str::plural($this->modelName));
    }

    /**
     * Check if soft deletes should be included.
     */
    protected function hasSoftDeletes(): bool
    {
        return $this->options['soft-deletes'] ?? false;
    }

    /**
     * Get the CSS framework.
     */
    protected function getCssFramework(): string
    {
        return $this->options['css'] ?? config('auto-crud.default_css', 'tailwind');
    }

    /**
     * Get common replacements.
     */
    protected function getCommonReplacements(): array
    {
        return [
            'model' => $this->modelName,
            'modelVariable' => $this->getModelVariable(),
            'modelVariablePlural' => $this->getModelVariablePlural(),
            'modelPlural' => $this->getModelPlural(),
            'modelPluralLower' => $this->getModelPluralLower(),
            'modelKebab' => $this->getModelKebab(),
            'table' => $this->getTableName(),
            'routeName' => $this->getRouteName(),
            'viewPath' => $this->getViewPath(),
        ];
    }
}
