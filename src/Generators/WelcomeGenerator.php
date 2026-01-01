<?php

namespace AutoCrud\Generators;

use Illuminate\Support\Str;

class WelcomeGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $css = $this->getCssFramework();
        $path = resource_path('views/welcome.blade.php');

        // Check if welcome already exists and force is not set
        if ($this->files->exists($path) && !($this->options['force'] ?? false)) {
            return [
                'path' => $path,
                'created' => false,
            ];
        }

        $content = $this->getStubContent("views/{$css}/welcome.blade.stub");
        $content = $this->replaceQuickLinks($content);

        $this->files->put($path, $content);

        return [
            'path' => $path,
            'created' => true,
        ];
    }

    /**
     * Replace quick links placeholder with detected models.
     */
    protected function replaceQuickLinks(string $content): string
    {
        $models = $this->detectModels();
        $css = $this->getCssFramework();

        if (empty($models)) {
            $quickLinks = $this->getEmptyQuickLinks($css);
        } else {
            $quickLinks = $this->generateQuickLinks($models, $css);
        }

        return str_replace('{{ quickLinks }}', $quickLinks, $content);
    }

    /**
     * Detect existing models with controllers.
     */
    protected function detectModels(): array
    {
        $models = [];
        $modelPath = app_path('Models');

        if (!$this->files->isDirectory($modelPath)) {
            return $models;
        }

        $files = $this->files->files($modelPath);

        foreach ($files as $file) {
            $name = $file->getFilenameWithoutExtension();

            // Skip User model
            if ($name === 'User') {
                continue;
            }

            // Check if controller exists
            $controllerPath = app_path("Http/Controllers/{$name}Controller.php");
            if ($this->files->exists($controllerPath)) {
                $models[] = $name;
            }
        }

        return $models;
    }

    /**
     * Generate quick links for models.
     */
    protected function generateQuickLinks(array $models, string $css): string
    {
        $links = [];
        $icons = ['📦', '📁', '📋', '🏷️', '⭐', '📝', '🔖', '📊'];

        foreach ($models as $index => $model) {
            $route = Str::kebab(Str::plural($model));
            $label = Str::plural($model);
            $icon = $icons[$index % count($icons)];

            if ($css === 'tailwind') {
                $links[] = $this->getTailwindCard($route, $label, $icon);
            } else {
                $links[] = $this->getBootstrapCard($route, $label, $icon);
            }
        }

        return implode("\n                    ", $links);
    }

    /**
     * Get Tailwind card for a model.
     */
    protected function getTailwindCard(string $route, string $label, string $icon): string
    {
        return <<<HTML
<a href="{{ route('{$route}.index') }}" class="block bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                        <div class="flex items-center">
                            <span class="text-3xl mr-4">{$icon}</span>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{$label}</h3>
                                <p class="text-gray-500 dark:text-gray-400">Beheer {$label}</p>
                            </div>
                        </div>
                    </a>
HTML;
    }

    /**
     * Get Bootstrap card for a model.
     */
    protected function getBootstrapCard(string $route, string $label, string $icon): string
    {
        return <<<HTML
<div class="col-md-4">
                    <a href="{{ route('{$route}.index') }}" class="card h-100 text-decoration-none shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <span class="fs-1 me-3">{$icon}</span>
                            <div>
                                <h5 class="card-title mb-1">{$label}</h5>
                                <p class="card-text text-muted small">Beheer {$label}</p>
                            </div>
                        </div>
                    </a>
                </div>
HTML;
    }

    /**
     * Get empty quick links placeholder.
     */
    protected function getEmptyQuickLinks(string $css): string
    {
        if ($css === 'tailwind') {
            return <<<HTML
<div class="col-span-full text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">
                            Nog geen modules. Maak je eerste CRUD aan met:
                            <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded ml-2">php artisan make:crud Product</code>
                        </p>
                    </div>
HTML;
        }

        return <<<HTML
<div class="col-12 text-center py-4">
                    <p class="text-muted">
                        Nog geen modules. Maak je eerste CRUD aan met:
                        <code class="bg-light px-2 py-1 rounded">php artisan make:crud Product</code>
                    </p>
                </div>
HTML;
    }
}
