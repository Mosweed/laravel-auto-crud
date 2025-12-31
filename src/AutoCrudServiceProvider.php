<?php

namespace AutoCrud;

use AutoCrud\Commands\MakeCrudCommand;
use AutoCrud\Commands\PublishLayoutCommand;
use Illuminate\Support\ServiceProvider;

class AutoCrudServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/auto-crud.php',
            'auto-crud'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerPublishing();
        }
    }

    /**
     * Register the package's commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            MakeCrudCommand::class,
            PublishLayoutCommand::class,
        ]);
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/auto-crud.php' => config_path('auto-crud.php'),
        ], 'auto-crud-config');

        // Publish stubs for customization
        $this->publishes([
            __DIR__ . '/stubs' => base_path('stubs/auto-crud'),
        ], 'auto-crud-stubs');

        // Publish Tailwind layout
        $this->publishes([
            __DIR__ . '/stubs/layouts/tailwind/app.blade.stub' => resource_path('views/components/app-layout.blade.php'),
        ], 'auto-crud-layout-tailwind');

        // Publish Bootstrap layout
        $this->publishes([
            __DIR__ . '/stubs/layouts/bootstrap/app.blade.stub' => resource_path('views/components/app-layout.blade.php'),
        ], 'auto-crud-layout-bootstrap');
    }
}
