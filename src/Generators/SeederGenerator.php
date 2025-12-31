<?php

namespace AutoCrud\Generators;

class SeederGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $content = $this->getStubContent('seeder.stub');
        $content = $this->replacePlaceholders($content, $this->getReplacements());

        $path = config('auto-crud.paths.seeders') . '/' . $this->modelName . 'Seeder.php';
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
            'count' => $this->options['seeder_count'] ?? 10,
        ];
    }
}
