<?php

namespace AutoCrud\Commands;

use AutoCrud\Generators\LayoutGenerator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class PublishLayoutCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'crud:layout
                            {--css=tailwind : CSS framework (tailwind, bootstrap)}
                            {--force : Overwrite existing layout}
                            {--models=* : Models to add to navigation}';

    /**
     * The console command description.
     */
    protected $description = 'Publish the Auto CRUD app layout with navigation';

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $css = $this->option('css');
        $force = $this->option('force');
        $models = $this->option('models');

        $this->info('Publishing Auto CRUD layout...');
        $this->newLine();

        // If no models specified, try to detect existing models
        if (empty($models)) {
            $models = $this->detectExistingModels();
        }

        $generator = new LayoutGenerator('', [
            'css' => $css,
            'force' => $force,
        ], $models);

        $result = $generator->generate();

        if ($result['created']) {
            $this->info('  <fg=green>✓</> Layout published: ' . $result['path']);

            if (!empty($models)) {
                $this->newLine();
                $this->info('Navigation items added for:');
                foreach ($models as $model) {
                    $this->line("  - {$model}");
                }
            }

            // Detect Tailwind version and inject theme if v4
            if ($css === 'tailwind') {
                $tailwindVersion = $generator->detectTailwindVersion();

                if ($tailwindVersion >= 4) {
                    $themeResult = $generator->injectTailwindTheme();

                    if ($themeResult['success']) {
                        $this->info('  <fg=green>✓</> Tailwind v4 theme colors added to app.css');
                    } else {
                        $this->line("  <fg=yellow>→</> {$themeResult['message']}");
                    }

                    $this->newLine();
                    $this->comment('Tailwind v4: Pas kleuren aan in resources/css/app.css onder @theme');
                } else {
                    $this->newLine();
                    $this->comment('Tailwind v3: Pas kleuren aan in tailwind.config.js');
                }
            }

            $this->newLine();
            $this->info('Gebruik de layout in je views met: <x-app-layout>');
        } else {
            $this->warn('  <fg=yellow>⚠</> Layout already exists. Use --force to overwrite.');
        }

        return Command::SUCCESS;
    }

    /**
     * Detect existing models in the app.
     */
    protected function detectExistingModels(): array
    {
        $models = [];
        $modelPath = app_path('Models');

        if (!$this->files->isDirectory($modelPath)) {
            return $models;
        }

        $files = $this->files->files($modelPath);

        foreach ($files as $file) {
            $name = $file->getFilenameWithoutExtension();

            // Skip User model (usually not a CRUD model)
            if ($name === 'User') {
                continue;
            }

            // Check if corresponding controller exists
            $controllerPath = app_path("Http/Controllers/{$name}Controller.php");
            if ($this->files->exists($controllerPath)) {
                $models[] = $name;
            }
        }

        return $models;
    }
}
