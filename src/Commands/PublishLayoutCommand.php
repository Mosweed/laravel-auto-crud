<?php

namespace AutoCrud\Commands;

use AutoCrud\Generators\LayoutGenerator;
use AutoCrud\Generators\WelcomeGenerator;
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
                            {--force : Overwrite existing files}
                            {--welcome : Also publish welcome page}
                            {--models=* : Models to add to navigation}';

    /**
     * The console command description.
     */
    protected $description = 'Publish the Auto CRUD app layout and welcome page';

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

        // Publish welcome page if requested
        if ($this->option('welcome')) {
            $this->publishWelcomePage($css, $force);
        }

        return Command::SUCCESS;
    }

    /**
     * Publish the welcome page.
     */
    protected function publishWelcomePage(string $css, bool $force): void
    {
        $this->newLine();
        $this->info('Publishing welcome page...');

        $generator = new WelcomeGenerator('', [
            'css' => $css,
            'force' => $force,
        ]);

        $result = $generator->generate();

        if ($result['created']) {
            $this->info('  <fg=green>✓</> Welcome page published: ' . $result['path']);
        } else {
            $this->warn('  <fg=yellow>⚠</> Welcome page already exists. Use --force to overwrite.');
        }
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
