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

        $added = $this->addRouteToFile($routeFile, $routeContent, $useStatement);

        return [
            'content' => $routeContent,
            'file' => $routeFile,
            'message' => $added ? 'Routes toegevoegd aan routes/api.php' : 'Routes bestaan al in routes/api.php',
            'added' => $added,
        ];
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
