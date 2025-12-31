<?php

namespace AutoCrud\Generators;

class PolicyGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $content = $this->getStubContent('policy.stub');
        $content = $this->replacePlaceholders($content, $this->getReplacements());

        $path = config('auto-crud.paths.policies') . '/' . $this->modelName . 'Policy.php';
        $created = $this->createFile($path, $content);

        return [
            'path' => $path,
            'created' => $created,
        ];
    }

    protected function getReplacements(): array
    {
        return array_merge($this->getCommonReplacements(), [
            'namespace' => config('auto-crud.namespaces.policies'),
            'modelNamespace' => config('auto-crud.namespaces.models'),
            'userModel' => 'App\\Models\\User',
            'userClass' => 'User',
            'softDeleteMethods' => $this->hasSoftDeletes() ? $this->generateSoftDeleteMethods() : '',
        ]);
    }

    protected function generateSoftDeleteMethods(): string
    {
        $model = $this->modelName;
        $variable = $this->getModelVariable();

        return <<<PHP

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User \$user, {$model} \${$variable}): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User \$user, {$model} \${$variable}): bool
    {
        return true;
    }
PHP;
    }
}
