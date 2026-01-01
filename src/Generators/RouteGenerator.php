<?php

namespace AutoCrud\Generators;

class RouteGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $results = [];
        $type = $this->options['type'] ?? 'both';

        if (in_array($type, ['web', 'both'])) {
            $results['web'] = $this->generateWebRoutes();
        }

        if (in_array($type, ['api', 'both'])) {
            $results['api'] = $this->generateApiRoutes();
        }

        return $results;
    }

    protected function generateWebRoutes(): array
    {
        $routeFile = base_path('routes/web.php');
        $routeContent = $this->getWebRouteContent();
        $useStatement = $this->getWebUseStatement();

        $added = $this->addRouteToFile($routeFile, $routeContent, $useStatement);

        return [
            'content' => $routeContent,
            'file' => $routeFile,
            'message' => $added ? 'Routes toegevoegd aan routes/web.php' : 'Routes bestaan al in routes/web.php',
            'added' => $added,
        ];
    }

    protected function generateApiRoutes(): array
    {
        $routeFile = base_path('routes/api.php');
        $routeContent = $this->getApiRouteContent();
        $useStatement = $this->getApiUseStatement();

        // Create api.php if it doesn't exist
        $created = false;
        if (!$this->files->exists($routeFile)) {
            $this->createApiRouteFile($routeFile);
            $this->registerApiRoutesInBootstrap();
            $created = true;
        }

        $added = $this->addRouteToFile($routeFile, $routeContent, $useStatement);

        $message = $added ? 'Routes toegevoegd aan routes/api.php' : 'Routes bestaan al in routes/api.php';
        if ($created) {
            $message = 'routes/api.php aangemaakt en routes toegevoegd';
        }

        return [
            'content' => $routeContent,
            'file' => $routeFile,
            'message' => $message,
            'added' => $added,
            'created' => $created,
        ];
    }

    /**
     * Create api.php route file.
     */
    protected function createApiRouteFile(string $file): void
    {
        $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

PHP;

        $this->files->put($file, $content);
    }

    /**
     * Register API routes in bootstrap/app.php for Laravel 11+.
     */
    protected function registerApiRoutesInBootstrap(): void
    {
        $bootstrapFile = base_path('bootstrap/app.php');

        if (!$this->files->exists($bootstrapFile)) {
            return;
        }

        $content = $this->files->get($bootstrapFile);

        // Check if api routes are already registered
        if (str_contains($content, 'api:') || str_contains($content, 'api.php')) {
            return;
        }

        // Find the web: line and add api: after it
        if (str_contains($content, 'web:') && str_contains($content, '->withRouting(')) {
            $content = preg_replace(
                "/(web:\s*__DIR__\s*\.\s*['\"]\.\.\/routes\/web\.php['\"],?)/",
                "$1\n        api: __DIR__.'/../routes/api.php',",
                $content
            );

            $this->files->put($bootstrapFile, $content);
        }
    }

    /**
     * Add route to file.
     */
    protected function addRouteToFile(string $file, string $routeContent, string $useStatement): bool
    {
        if (!$this->files->exists($file)) {
            return false;
        }

        $content = $this->files->get($file);
        $routeName = $this->getRouteName();

        // Check if route already exists
        if (str_contains($content, "'{$routeName}'") || str_contains($content, "\"{$routeName}\"")) {
            return false;
        }

        // Add use statement if not exists
        if (!str_contains($content, $useStatement)) {
            $content = $this->addUseStatement($content, $useStatement);
        }

        // Add route at the end of file
        $content = rtrim($content) . "\n\n" . $routeContent . "\n";

        $this->files->put($file, $content);

        return true;
    }

    /**
     * Add use statement to file.
     */
    protected function addUseStatement(string $content, string $useStatement): string
    {
        // Find the last use statement
        if (preg_match('/^use\s+[^;]+;/m', $content)) {
            // Find position after last use statement
            preg_match_all('/^use\s+[^;]+;\s*$/m', $content, $matches, PREG_OFFSET_CAPTURE);

            if (!empty($matches[0])) {
                $lastMatch = end($matches[0]);
                $insertPosition = $lastMatch[1] + strlen($lastMatch[0]);

                return substr($content, 0, $insertPosition) . $useStatement . "\n" . substr($content, $insertPosition);
            }
        }

        // If no use statements found, add after <?php
        if (preg_match('/<\?php\s*/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPosition = $matches[0][1] + strlen($matches[0][0]);
            return substr($content, 0, $insertPosition) . "\n" . $useStatement . "\n" . substr($content, $insertPosition);
        }

        return $useStatement . "\n" . $content;
    }

    protected function getWebUseStatement(): string
    {
        $controller = $this->modelName . 'Controller';
        $namespace = config('auto-crud.namespaces.controllers', 'App\\Http\\Controllers');

        return "use {$namespace}\\{$controller};";
    }

    protected function getApiUseStatement(): string
    {
        $controller = $this->modelName . 'Controller';
        $namespace = config('auto-crud.namespaces.api_controllers', 'App\\Http\\Controllers\\Api');

        return "use {$namespace}\\{$controller};";
    }

    protected function getWebRouteContent(): string
    {
        $controller = $this->modelName . 'Controller';
        $route = $this->getRouteName();

        $softDeleteRoutes = '';
        if ($this->hasSoftDeletes()) {
            $softDeleteRoutes = "\nRoute::post('{$route}/{id}/restore', [{$controller}::class, 'restore'])->name('{$route}.restore');" .
                "\nRoute::delete('{$route}/{id}/force-delete', [{$controller}::class, 'forceDelete'])->name('{$route}.force-delete');";
        }

        return "Route::resource('{$route}', {$controller}::class);{$softDeleteRoutes}";
    }

    protected function getApiRouteContent(): string
    {
        $controller = $this->modelName . 'Controller';
        $route = $this->getRouteName();

        $softDeleteRoutes = '';
        if ($this->hasSoftDeletes()) {
            $softDeleteRoutes = "\nRoute::post('{$route}/{id}/restore', [{$controller}::class, 'restore'])->name('api.{$route}.restore');" .
                "\nRoute::delete('{$route}/{id}/force-delete', [{$controller}::class, 'forceDelete'])->name('api.{$route}.force-delete');";
        }

        return "Route::apiResource('{$route}', {$controller}::class);{$softDeleteRoutes}";
    }
}
