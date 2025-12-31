<?php

namespace AutoCrud\Generators;

use Illuminate\Support\Str;

class ControllerGenerator extends BaseGenerator
{
    protected array $relationships;

    public function __construct(string $modelName, array $options = [], array $relationships = [])
    {
        parent::__construct($modelName, $options);
        $this->relationships = $relationships;
    }

    public function generate(): array
    {
        $results = [];
        $type = $this->options['type'] ?? 'both';

        if (in_array($type, ['api', 'both'])) {
            $results['api'] = $this->generateApiController();
        }

        if (in_array($type, ['web', 'both'])) {
            $results['web'] = $this->generateWebController();
        }

        return $results;
    }

    protected function generateApiController(): array
    {
        $content = $this->getStubContent('controller.api.stub');
        $content = $this->replacePlaceholders($content, $this->getApiReplacements());

        $path = config('auto-crud.paths.api_controllers') . '/' . $this->modelName . 'Controller.php';
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function generateWebController(): array
    {
        $content = $this->getStubContent('controller.web.stub');
        $content = $this->replacePlaceholders($content, $this->getWebReplacements());

        $path = config('auto-crud.paths.controllers') . '/' . $this->modelName . 'Controller.php';
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function getApiReplacements(): array
    {
        return array_merge($this->getCommonReplacements(), [
            'namespace' => config('auto-crud.namespaces.api_controllers'),
            'modelNamespace' => config('auto-crud.namespaces.models'),
            'requestNamespace' => config('auto-crud.namespaces.requests'),
            'resourceNamespace' => config('auto-crud.namespaces.resources'),
            'loadRelationships' => $this->generateLoadRelationships(),
            'withTrashed' => $this->generateWithTrashed(),
            'softDeleteMethods' => $this->hasSoftDeletes() ? $this->generateSoftDeleteMethodsApi() : '',
        ]);
    }

    protected function getWebReplacements(): array
    {
        return array_merge($this->getCommonReplacements(), [
            'namespace' => config('auto-crud.namespaces.controllers'),
            'modelNamespace' => config('auto-crud.namespaces.models'),
            'requestNamespace' => config('auto-crud.namespaces.requests'),
            'loadRelationships' => $this->generateLoadRelationships(),
            'withTrashed' => $this->generateWithTrashed(),
            'softDeleteMethods' => $this->hasSoftDeletes() ? $this->generateSoftDeleteMethodsWeb() : '',
            'relatedModelImports' => $this->generateRelatedModelImports(),
            'loadRelatedModelsCreate' => $this->generateLoadRelatedModels(),
            'loadRelatedModelsEdit' => $this->generateLoadRelatedModels(),
            'compactRelatedCreate' => $this->generateCompactRelated(),
            'compactRelatedEdit' => $this->generateCompactRelatedEdit(),
        ]);
    }

    protected function generateLoadRelationships(): string
    {
        if (empty($this->relationships)) {
            return '';
        }

        $relations = array_map(fn ($rel) => "'{$rel['method_name']}'", $this->relationships);
        $relationsString = implode(', ', $relations);

        return "\$" . $this->getModelVariable() . "->load([{$relationsString}]);";
    }

    protected function generateWithTrashed(): string
    {
        if (!$this->hasSoftDeletes()) {
            return '';
        }

        return "->when(\$request->input('trashed') === '1', fn (\$q) => \$q->withTrashed())
            ->when(\$request->input('trashed') === 'only', fn (\$q) => \$q->onlyTrashed())";
    }

    protected function generateSoftDeleteMethodsApi(): string
    {
        $model = $this->modelName;
        $variable = $this->getModelVariable();

        return <<<PHP

    /**
     * Restore the specified resource from trash.
     */
    public function restore(int \$id): JsonResponse
    {
        \${$variable} = {$model}::onlyTrashed()->findOrFail(\$id);
        \$this->authorize('restore', \${$variable});
        \${$variable}->restore();

        return response()->json([
            'message' => '{$model} succesvol hersteld.',
            'data' => new {$model}Resource(\${$variable}),
        ]);
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(int \$id): JsonResponse
    {
        \${$variable} = {$model}::onlyTrashed()->findOrFail(\$id);
        \$this->authorize('forceDelete', \${$variable});
        \${$variable}->forceDelete();

        return response()->json([
            'message' => '{$model} permanent verwijderd.',
        ]);
    }
PHP;
    }

    protected function generateSoftDeleteMethodsWeb(): string
    {
        $model = $this->modelName;
        $variable = $this->getModelVariable();
        $route = $this->getRouteName();

        return <<<PHP

    /**
     * Restore the specified resource from trash.
     */
    public function restore(int \$id): RedirectResponse
    {
        \${$variable} = {$model}::onlyTrashed()->findOrFail(\$id);
        \$this->authorize('restore', \${$variable});
        \${$variable}->restore();

        return redirect()
            ->route('{$route}.index')
            ->with('success', '{$model} succesvol hersteld.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(int \$id): RedirectResponse
    {
        \${$variable} = {$model}::onlyTrashed()->findOrFail(\$id);
        \$this->authorize('forceDelete', \${$variable});
        \${$variable}->forceDelete();

        return redirect()
            ->route('{$route}.index')
            ->with('success', '{$model} permanent verwijderd.');
    }
PHP;
    }

    /**
     * Get belongsTo relationships.
     */
    protected function getBelongsToRelationships(): array
    {
        return array_filter($this->relationships, fn ($rel) => $rel['type'] === 'belongsTo');
    }

    /**
     * Generate imports for related models.
     */
    protected function generateRelatedModelImports(): string
    {
        $belongsTo = $this->getBelongsToRelationships();

        if (empty($belongsTo)) {
            return '';
        }

        $imports = [];
        $modelNamespace = config('auto-crud.namespaces.models');

        foreach ($belongsTo as $rel) {
            $imports[] = "use {$modelNamespace}\\{$rel['related_model']};";
        }

        return implode("\n", array_unique($imports));
    }

    /**
     * Generate code to load related models for forms.
     */
    protected function generateLoadRelatedModels(): string
    {
        $belongsTo = $this->getBelongsToRelationships();

        if (empty($belongsTo)) {
            return '';
        }

        $lines = [];
        foreach ($belongsTo as $rel) {
            $varName = Str::camel(Str::plural($rel['related_model']));
            $lines[] = "        \${$varName} = {$rel['related_model']}::all();";
        }

        return implode("\n", $lines);
    }

    /**
     * Generate compact statement for related models in create.
     */
    protected function generateCompactRelated(): string
    {
        $belongsTo = $this->getBelongsToRelationships();

        if (empty($belongsTo)) {
            return '';
        }

        $vars = [];
        foreach ($belongsTo as $rel) {
            $vars[] = "'" . Str::camel(Str::plural($rel['related_model'])) . "'";
        }

        return ', compact(' . implode(', ', $vars) . ')';
    }

    /**
     * Generate compact statement for related models in edit.
     */
    protected function generateCompactRelatedEdit(): string
    {
        $belongsTo = $this->getBelongsToRelationships();

        if (empty($belongsTo)) {
            return '';
        }

        $vars = [];
        foreach ($belongsTo as $rel) {
            $vars[] = "'" . Str::camel(Str::plural($rel['related_model'])) . "'";
        }

        return ', ' . implode(', ', $vars);
    }
}
