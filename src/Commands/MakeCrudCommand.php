<?php

namespace AutoCrud\Commands;

use AutoCrud\Generators\ControllerGenerator;
use AutoCrud\Generators\FactoryGenerator;
use AutoCrud\Generators\LayoutGenerator;
use AutoCrud\Generators\LivewireGenerator;
use AutoCrud\Generators\MigrationGenerator;
use AutoCrud\Generators\ModelGenerator;
use AutoCrud\Generators\PolicyGenerator;
use AutoCrud\Generators\RequestGenerator;
use AutoCrud\Generators\ResourceGenerator;
use AutoCrud\Generators\RouteGenerator;
use AutoCrud\Generators\SeederGenerator;
use AutoCrud\Generators\TestGenerator;
use AutoCrud\Generators\ViewGenerator;
use AutoCrud\Services\SchemaAnalyzer;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeCrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'make:crud {name? : The name of the model (optional when using --json)}
                            {--type=both : Output type (api, web, both, livewire)}
                            {--css=tailwind : CSS framework (tailwind, bootstrap)}
                            {--all : Generate migration, factory, seeder, and tests}
                            {--force : Overwrite existing files}
                            {--no-policy : Skip policy generation}
                            {--no-requests : Skip form request generation}
                            {--api-resource : Generate API resource}
                            {--soft-deletes : Add soft delete support}
                            {--timestamps : Add timestamps to migration}
                            {--livewire : Generate Livewire components}
                            {--tests : Generate feature and unit tests (Pest/PHPUnit)}
                            {--table= : Use existing table for schema analysis}
                            {--fields= : Define fields (e.g. "title:string,body:text,is_active:boolean")}
                            {--belongsTo=* : Add belongsTo relationships (e.g. --belongsTo=User --belongsTo=Category)}
                            {--hasMany=* : Add hasMany relationships (e.g. --hasMany=Comment --hasMany=Tag)}
                            {--belongsToMany=* : Add belongsToMany relationships (e.g. --belongsToMany=Tag)}
                            {--json= : Path to JSON configuration file}
                            {--add-to-nav : Add model to navigation in app-layout}';

    /**
     * The console command description.
     */
    protected $description = 'Generate complete CRUD scaffolding for a model';

    /**
     * The options for generators.
     */
    protected array $options = [];

    /**
     * The column information from schema.
     */
    protected array $columns = [];

    /**
     * The detected relationships.
     */
    protected array $relationships = [];

    /**
     * Track created files for rollback.
     */
    protected array $createdFiles = [];

    /**
     * Track created directories for rollback.
     */
    protected array $createdDirectories = [];

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
        // Check if using JSON configuration
        if ($this->option('json')) {
            return $this->handleJsonConfig();
        }

        $name = $this->argument('name');

        if (empty($name)) {
            $this->error('Model name is required. Use --json for JSON configuration.');
            return Command::FAILURE;
        }

        $modelName = Str::studly($name);

        $this->options = [
            'type' => $this->option('livewire') ? 'livewire' : $this->option('type'),
            'css' => $this->option('css'),
            'force' => $this->option('force'),
            'soft-deletes' => $this->option('soft-deletes'),
            'all' => $this->option('all'),
            'api-resource' => $this->option('api-resource'),
            'no-policy' => $this->option('no-policy'),
            'no-requests' => $this->option('no-requests'),
        ];

        $this->info("Generating CRUD for {$modelName}...");
        $this->newLine();

        // Parse fields from command line
        $this->parseFields();

        // Parse relationships from command line
        $this->parseRelationships();

        // Analyze existing table if specified (only if no fields provided)
        if (empty($this->columns)) {
            $this->analyzeSchema($modelName);
        }

        try {
            // Generate all components
            $this->generateModel($modelName);
            $this->generateController($modelName);
            $this->generateRequests($modelName);
            $this->generatePolicy($modelName);

            if ($this->option('type') === 'livewire' || $this->option('livewire')) {
                $this->generateLivewire($modelName);
            } else {
                $this->generateViews($modelName);
            }

            if ($this->option('api-resource') || in_array($this->options['type'], ['api', 'both'])) {
                $this->generateResource($modelName);
            }

            if ($this->option('all')) {
                $this->generateMigration($modelName);
                $this->generateFactory($modelName);
                $this->generateSeeder($modelName);
                $this->generateTests($modelName);
            }

            if ($this->option('tests') && !$this->option('all')) {
                $this->generateTests($modelName);
            }

            $this->generateRoutes($modelName);

            // Add to navigation if requested
            if ($this->option('add-to-nav')) {
                $this->addToNavigation($modelName);
            }

            $this->newLine();
            $this->info('CRUD generation completed successfully!');
            $this->info('Created ' . count($this->createdFiles) . ' files.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('An error occurred: ' . $e->getMessage());
            $this->newLine();

            // Rollback all created files
            $this->rollback();

            return Command::FAILURE;
        }
    }

    /**
     * Rollback all created files and directories.
     */
    protected function rollback(): void
    {
        if (empty($this->createdFiles) && empty($this->createdDirectories)) {
            return;
        }

        $this->warn('Rolling back created files...');

        // Delete created files
        foreach ($this->createdFiles as $file) {
            if ($this->files->exists($file)) {
                $this->files->delete($file);
                $this->line("  <fg=red>✗</> Deleted: {$file}");
            }
        }

        // Delete created directories (in reverse order to handle nested dirs)
        $directories = array_reverse($this->createdDirectories);
        foreach ($directories as $directory) {
            if ($this->files->isDirectory($directory) && $this->isDirectoryEmpty($directory)) {
                $this->files->deleteDirectory($directory);
                $this->line("  <fg=red>✗</> Deleted directory: {$directory}");
            }
        }

        $this->newLine();
        $this->info('Rollback completed. ' . count($this->createdFiles) . ' files removed.');
    }

    /**
     * Check if a directory is empty.
     */
    protected function isDirectoryEmpty(string $directory): bool
    {
        return count($this->files->files($directory)) === 0
            && count($this->files->directories($directory)) === 0;
    }

    /**
     * Track a created file.
     */
    protected function trackFile(string $path): void
    {
        if (!in_array($path, $this->createdFiles)) {
            $this->createdFiles[] = $path;
        }
    }

    /**
     * Track a created directory.
     */
    protected function trackDirectory(string $path): void
    {
        if (!in_array($path, $this->createdDirectories)) {
            $this->createdDirectories[] = $path;
        }
    }

    /**
     * Analyze the database schema if table exists.
     */
    protected function analyzeSchema(string $modelName): void
    {
        $tableName = $this->option('table') ?? Str::snake(Str::plural($modelName));

        if (Schema::hasTable($tableName)) {
            $this->info("Analyzing table: {$tableName}");

            try {
                $analyzer = new SchemaAnalyzer($tableName);
                $this->columns = $analyzer->getColumns();
                $this->relationships = $analyzer->detectRelationships();

                // Auto-detect soft deletes
                if ($analyzer->hasSoftDeletes() && !$this->option('soft-deletes')) {
                    $this->options['soft-deletes'] = true;
                    $this->info('  → Soft deletes detected');
                }

                $this->info('  → Found ' . count($this->columns) . ' columns');
                $this->info('  → Found ' . count($this->relationships) . ' relationships');
            } catch (\Exception $e) {
                $this->warn("Could not analyze table: {$e->getMessage()}");
            }
        } else {
            $this->warn("Table '{$tableName}' does not exist. Using default scaffolding.");
        }
    }

    /**
     * Generate the model.
     */
    protected function generateModel(string $modelName): void
    {
        $this->task('Generating Model', function () use ($modelName) {
            $generator = new ModelGenerator($modelName, $this->options, $this->columns, $this->relationships);
            $result = $generator->generate();

            if ($result['created']) {
                $this->trackFile($result['path']);
            }

            return $result['created'];
        });
    }

    /**
     * Generate the controller(s).
     */
    protected function generateController(string $modelName): void
    {
        $type = $this->options['type'];

        if ($type === 'livewire') {
            return; // Livewire doesn't need traditional controllers
        }

        $this->task('Generating Controller(s)', function () use ($modelName) {
            $generator = new ControllerGenerator($modelName, $this->options, $this->relationships);
            $results = $generator->generate();

            foreach ($results as $result) {
                if (isset($result['created']) && $result['created']) {
                    $this->trackFile($result['path']);
                    $this->trackDirectory(dirname($result['path']));
                }
            }

            return !empty($results);
        });
    }

    /**
     * Generate the form requests.
     */
    protected function generateRequests(string $modelName): void
    {
        if ($this->option('no-requests')) {
            return;
        }

        $this->task('Generating Form Requests', function () use ($modelName) {
            $generator = new RequestGenerator($modelName, $this->options, $this->columns);
            $results = $generator->generate();

            foreach ($results as $result) {
                if (isset($result['created']) && $result['created']) {
                    $this->trackFile($result['path']);
                    $this->trackDirectory(dirname($result['path']));
                }
            }

            return !empty($results);
        });
    }

    /**
     * Generate the policy.
     */
    protected function generatePolicy(string $modelName): void
    {
        if ($this->option('no-policy')) {
            return;
        }

        $this->task('Generating Policy', function () use ($modelName) {
            $generator = new PolicyGenerator($modelName, $this->options);
            $result = $generator->generate();

            if ($result['created']) {
                $this->trackFile($result['path']);
            }

            return $result['created'];
        });
    }

    /**
     * Generate the views.
     */
    protected function generateViews(string $modelName): void
    {
        $type = $this->options['type'];

        if (!in_array($type, ['web', 'both'])) {
            return;
        }

        $this->task('Generating Views', function () use ($modelName) {
            $generator = new ViewGenerator($modelName, $this->options, $this->columns);
            $results = $generator->generate();

            foreach ($results as $result) {
                if (isset($result['created']) && $result['created']) {
                    $this->trackFile($result['path']);
                    $this->trackDirectory(dirname($result['path']));
                }
            }

            return !empty($results);
        });
    }

    /**
     * Generate Livewire components.
     */
    protected function generateLivewire(string $modelName): void
    {
        $this->task('Generating Livewire Components', function () use ($modelName) {
            $generator = new LivewireGenerator($modelName, $this->options, $this->columns);
            $results = $generator->generate();

            foreach ($results as $result) {
                if (isset($result['created']) && $result['created']) {
                    $this->trackFile($result['path']);
                    $this->trackDirectory(dirname($result['path']));
                }
            }

            return !empty($results);
        });
    }

    /**
     * Generate the API resource.
     */
    protected function generateResource(string $modelName): void
    {
        $this->task('Generating API Resource', function () use ($modelName) {
            $generator = new ResourceGenerator($modelName, $this->options, $this->columns, $this->relationships);
            $result = $generator->generate();

            if ($result['created']) {
                $this->trackFile($result['path']);
            }

            return $result['created'];
        });
    }

    /**
     * Generate the migration.
     */
    protected function generateMigration(string $modelName): void
    {
        $this->task('Generating Migration', function () use ($modelName) {
            $generator = new MigrationGenerator($modelName, $this->options, $this->columns);
            $result = $generator->generate();

            if ($result['created']) {
                $this->trackFile($result['path']);
            }

            return $result['created'];
        });
    }

    /**
     * Generate the factory.
     */
    protected function generateFactory(string $modelName): void
    {
        $this->task('Generating Factory', function () use ($modelName) {
            $generator = new FactoryGenerator($modelName, $this->options, $this->columns);
            $result = $generator->generate();

            if ($result['created']) {
                $this->trackFile($result['path']);
            }

            return $result['created'];
        });
    }

    /**
     * Generate the seeder.
     */
    protected function generateSeeder(string $modelName): void
    {
        $this->task('Generating Seeder', function () use ($modelName) {
            $generator = new SeederGenerator($modelName, $this->options);
            $result = $generator->generate();

            if ($result['created']) {
                $this->trackFile($result['path']);
            }

            return $result['created'];
        });
    }

    /**
     * Generate the tests.
     */
    protected function generateTests(string $modelName): void
    {
        $this->task('Generating Tests', function () use ($modelName) {
            $generator = new TestGenerator($modelName, $this->options, $this->columns, $this->relationships);
            $results = $generator->generate();

            foreach ($results as $result) {
                if (isset($result['created']) && $result['created']) {
                    $this->trackFile($result['path']);
                    $this->trackDirectory(dirname($result['path']));
                }
            }

            return !empty($results);
        });
    }

    /**
     * Generate routes.
     */
    protected function generateRoutes(string $modelName): void
    {
        $this->task('Generating Routes', function () use ($modelName) {
            $generator = new RouteGenerator($modelName, $this->options);
            $results = $generator->generate();

            $addedAny = false;
            foreach ($results as $type => $route) {
                if ($route['added'] ?? false) {
                    $addedAny = true;
                    $this->trackFile($route['file']);
                }
            }

            return $addedAny;
        });
    }

    /**
     * Add model to navigation.
     */
    protected function addToNavigation(string $modelName): void
    {
        $this->task('Adding to Navigation', function () use ($modelName) {
            $generator = new LayoutGenerator($modelName, $this->options);

            return $generator->addNavItem($modelName);
        });
    }

    /**
     * Run a task with output.
     */
    protected function task(string $title, callable $task): void
    {
        $this->output->write("  {$title}...");

        try {
            $result = $task();

            if ($result) {
                $this->output->writeln(' <fg=green>✓</>');
            } else {
                $this->output->writeln(' <fg=yellow>⚠ (skipped or exists)</>');
            }
        } catch (\Exception $e) {
            $this->output->writeln(' <fg=red>✗ FAILED</>');
            throw $e; // Re-throw to trigger rollback
        }
    }

    /**
     * Parse fields from --fields option.
     *
     * Format: "name:type:modifiers,name2:type2"
     * Examples:
     *   - title:string
     *   - title:string:nullable
     *   - title:string:255:nullable
     *   - body:text
     *   - is_active:boolean
     *   - user_id:foreignId
     *   - price:decimal:8,2
     */
    protected function parseFields(): void
    {
        $fieldsOption = $this->option('fields');

        if (empty($fieldsOption)) {
            return;
        }

        $fields = explode(',', $fieldsOption);

        foreach ($fields as $field) {
            $parts = explode(':', trim($field));
            $name = $parts[0];
            $type = $parts[1] ?? 'string';

            // Parse modifiers
            $nullable = false;
            $length = null;
            $unique = false;

            for ($i = 2; $i < count($parts); $i++) {
                $modifier = strtolower($parts[$i]);
                if ($modifier === 'nullable') {
                    $nullable = true;
                } elseif ($modifier === 'unique') {
                    $unique = true;
                } elseif (is_numeric($modifier)) {
                    $length = (int) $modifier;
                }
            }

            // Map common types
            $typeMap = [
                'str' => 'string',
                'int' => 'integer',
                'bool' => 'boolean',
                'txt' => 'text',
                'json' => 'json',
                'date' => 'date',
                'datetime' => 'dateTime',
                'time' => 'time',
                'float' => 'float',
                'double' => 'float',
                'decimal' => 'decimal',
                'foreignId' => 'bigInteger',
                'foreign' => 'bigInteger',
            ];

            $mappedType = $typeMap[$type] ?? $type;

            // Check if it's a foreign key
            $isForeign = Str::endsWith($name, '_id') || in_array($type, ['foreignId', 'foreign']);

            $this->columns[$name] = [
                'name' => $name,
                'type' => $mappedType,
                'doctrine_type' => $mappedType,
                'length' => $length ?? ($mappedType === 'string' ? 255 : null),
                'nullable' => $nullable,
                'default' => null,
                'unsigned' => $isForeign,
                'autoincrement' => false,
                'is_primary' => false,
                'is_foreign' => $isForeign,
                'is_unique' => $unique,
            ];

            // Auto-add belongsTo relationship for foreign keys
            if ($isForeign) {
                $relatedModel = Str::studly(Str::beforeLast($name, '_id'));
                $this->relationships[] = [
                    'type' => 'belongsTo',
                    'column' => $name,
                    'related_model' => $relatedModel,
                    'method_name' => Str::camel(Str::beforeLast($name, '_id')),
                ];
            }
        }

        $this->info('  → Parsed ' . count($this->columns) . ' fields from --fields option');
    }

    /**
     * Parse relationships from command options.
     */
    protected function parseRelationships(): void
    {
        // Parse belongsTo relationships
        $belongsTo = $this->option('belongsTo');
        if (!empty($belongsTo)) {
            foreach ($belongsTo as $related) {
                $relatedModel = Str::studly($related);
                $foreignKey = Str::snake($related) . '_id';

                // Check if relationship already exists (from foreign key)
                $exists = collect($this->relationships)->contains(function ($rel) use ($relatedModel) {
                    return $rel['related_model'] === $relatedModel && $rel['type'] === 'belongsTo';
                });

                if (!$exists) {
                    $this->relationships[] = [
                        'type' => 'belongsTo',
                        'column' => $foreignKey,
                        'related_model' => $relatedModel,
                        'method_name' => Str::camel($related),
                    ];

                    // Also add the foreign key column if not exists
                    if (!isset($this->columns[$foreignKey])) {
                        $this->columns[$foreignKey] = [
                            'name' => $foreignKey,
                            'type' => 'bigInteger',
                            'doctrine_type' => 'bigint',
                            'length' => null,
                            'nullable' => false,
                            'default' => null,
                            'unsigned' => true,
                            'autoincrement' => false,
                            'is_primary' => false,
                            'is_foreign' => true,
                            'is_unique' => false,
                        ];
                    }
                }
            }
        }

        // Parse hasMany relationships
        $hasMany = $this->option('hasMany');
        if (!empty($hasMany)) {
            foreach ($hasMany as $related) {
                $relatedModel = Str::studly($related);
                $this->relationships[] = [
                    'type' => 'hasMany',
                    'related_model' => $relatedModel,
                    'method_name' => Str::camel(Str::plural($related)),
                ];
            }
        }

        // Parse belongsToMany relationships
        $belongsToMany = $this->option('belongsToMany');
        if (!empty($belongsToMany)) {
            foreach ($belongsToMany as $related) {
                $relatedModel = Str::studly($related);
                $this->relationships[] = [
                    'type' => 'belongsToMany',
                    'related_model' => $relatedModel,
                    'method_name' => Str::camel(Str::plural($related)),
                ];
            }
        }

        if (!empty($belongsTo) || !empty($hasMany) || !empty($belongsToMany)) {
            $this->info('  → Parsed ' . count($this->relationships) . ' relationships');
        }
    }

    /**
     * Handle JSON configuration file.
     */
    protected function handleJsonConfig(): int
    {
        $jsonPath = $this->option('json');

        if (!$this->files->exists($jsonPath)) {
            $this->error("JSON file not found: {$jsonPath}");
            return Command::FAILURE;
        }

        $json = $this->files->get($jsonPath);
        $config = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON: ' . json_last_error_msg());
            return Command::FAILURE;
        }

        // Check if it's a single model or multiple models
        if (isset($config['models'])) {
            return $this->handleMultipleModels($config);
        }

        // Single model configuration
        return $this->handleSingleModel($config);
    }

    /**
     * Handle multiple models from JSON.
     */
    protected function handleMultipleModels(array $config): int
    {
        $globalOptions = $config['options'] ?? [];
        $models = $config['models'];
        $totalCreated = 0;

        $this->info('Generating CRUDs from JSON configuration...');
        $this->newLine();

        foreach ($models as $modelConfig) {
            // Merge global options with model-specific options
            $modelConfig['options'] = array_merge($globalOptions, $modelConfig['options'] ?? []);

            $result = $this->generateFromConfig($modelConfig);

            if ($result === Command::FAILURE) {
                return Command::FAILURE;
            }

            $totalCreated += count($this->createdFiles);
            $this->createdFiles = [];
            $this->createdDirectories = [];
            $this->columns = [];
            $this->relationships = [];

            $this->newLine();
        }

        $this->info("All CRUDs generated successfully! Total: {$totalCreated} files.");

        return Command::SUCCESS;
    }

    /**
     * Handle single model from JSON.
     */
    protected function handleSingleModel(array $config): int
    {
        return $this->generateFromConfig($config);
    }

    /**
     * Generate CRUD from configuration array.
     */
    protected function generateFromConfig(array $config): int
    {
        if (!isset($config['name'])) {
            $this->error('Model name is required in JSON configuration.');
            return Command::FAILURE;
        }

        $modelName = Str::studly($config['name']);
        $options = $config['options'] ?? [];

        $this->options = [
            'type' => $options['livewire'] ?? false ? 'livewire' : ($options['type'] ?? 'both'),
            'css' => $options['css'] ?? 'tailwind',
            'force' => $options['force'] ?? $this->option('force'),
            'soft-deletes' => $options['softDeletes'] ?? $options['soft-deletes'] ?? false,
            'all' => $options['all'] ?? false,
            'api-resource' => $options['apiResource'] ?? $options['api-resource'] ?? false,
            'no-policy' => $options['noPolicy'] ?? $options['no-policy'] ?? false,
            'no-requests' => $options['noRequests'] ?? $options['no-requests'] ?? false,
        ];

        $this->info("Generating CRUD for {$modelName}...");
        $this->newLine();

        // Parse fields from JSON
        if (isset($config['fields'])) {
            $this->parseFieldsFromJson($config['fields']);
        }

        // Parse relationships from JSON
        if (isset($config['relationships'])) {
            $this->parseRelationshipsFromJson($config['relationships']);
        }

        // Analyze existing table if no fields provided
        if (empty($this->columns) && isset($options['table'])) {
            $this->analyzeSchema($modelName);
        }

        try {
            $this->generateModel($modelName);
            $this->generateController($modelName);
            $this->generateRequests($modelName);
            $this->generatePolicy($modelName);

            if ($this->options['type'] === 'livewire') {
                $this->generateLivewire($modelName);
            } else {
                $this->generateViews($modelName);
            }

            if ($this->options['api-resource'] || in_array($this->options['type'], ['api', 'both'])) {
                $this->generateResource($modelName);
            }

            if ($this->options['all']) {
                $this->generateMigration($modelName);
                $this->generateFactory($modelName);
                $this->generateSeeder($modelName);
                $this->generateTests($modelName);
            }

            if ($options['tests'] ?? false) {
                $this->generateTests($modelName);
            }

            $this->generateRoutes($modelName);

            $this->newLine();
            $this->info('CRUD generation completed successfully!');
            $this->info('Created ' . count($this->createdFiles) . ' files.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('An error occurred: ' . $e->getMessage());
            $this->newLine();
            $this->rollback();

            return Command::FAILURE;
        }
    }

    /**
     * Parse fields from JSON configuration.
     */
    protected function parseFieldsFromJson(array $fields): void
    {
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'string';
            $nullable = $field['nullable'] ?? false;
            $unique = $field['unique'] ?? false;
            $length = $field['length'] ?? null;
            $default = $field['default'] ?? null;

            // Map common types
            $typeMap = [
                'str' => 'string',
                'int' => 'integer',
                'bool' => 'boolean',
                'txt' => 'text',
                'foreignId' => 'bigInteger',
                'foreign' => 'bigInteger',
            ];

            $mappedType = $typeMap[$type] ?? $type;
            $isForeign = Str::endsWith($name, '_id') || in_array($type, ['foreignId', 'foreign']);

            $this->columns[$name] = [
                'name' => $name,
                'type' => $mappedType,
                'doctrine_type' => $mappedType,
                'length' => $length ?? ($mappedType === 'string' ? 255 : null),
                'nullable' => $nullable,
                'default' => $default,
                'unsigned' => $isForeign,
                'autoincrement' => false,
                'is_primary' => false,
                'is_foreign' => $isForeign,
                'is_unique' => $unique,
            ];

            // Auto-add belongsTo relationship for foreign keys
            if ($isForeign) {
                $relatedModel = Str::studly(Str::beforeLast($name, '_id'));
                $this->relationships[] = [
                    'type' => 'belongsTo',
                    'column' => $name,
                    'related_model' => $relatedModel,
                    'method_name' => Str::camel(Str::beforeLast($name, '_id')),
                ];
            }
        }

        $this->info('  → Parsed ' . count($this->columns) . ' fields from JSON');
    }

    /**
     * Parse relationships from JSON configuration.
     */
    protected function parseRelationshipsFromJson(array $relationships): void
    {
        foreach ($relationships as $rel) {
            $type = $rel['type'];
            $related = $rel['model'];
            $relatedModel = Str::studly($related);

            switch ($type) {
                case 'belongsTo':
                    $foreignKey = $rel['foreignKey'] ?? Str::snake($related) . '_id';
                    $exists = collect($this->relationships)->contains(function ($r) use ($relatedModel, $type) {
                        return $r['related_model'] === $relatedModel && $r['type'] === $type;
                    });

                    if (!$exists) {
                        $this->relationships[] = [
                            'type' => 'belongsTo',
                            'column' => $foreignKey,
                            'related_model' => $relatedModel,
                            'method_name' => $rel['method'] ?? Str::camel($related),
                        ];

                        if (!isset($this->columns[$foreignKey])) {
                            $this->columns[$foreignKey] = [
                                'name' => $foreignKey,
                                'type' => 'bigInteger',
                                'doctrine_type' => 'bigint',
                                'length' => null,
                                'nullable' => $rel['nullable'] ?? false,
                                'default' => null,
                                'unsigned' => true,
                                'autoincrement' => false,
                                'is_primary' => false,
                                'is_foreign' => true,
                                'is_unique' => false,
                            ];
                        }
                    }
                    break;

                case 'hasMany':
                    $this->relationships[] = [
                        'type' => 'hasMany',
                        'related_model' => $relatedModel,
                        'method_name' => $rel['method'] ?? Str::camel(Str::plural($related)),
                    ];
                    break;

                case 'hasOne':
                    $this->relationships[] = [
                        'type' => 'hasOne',
                        'related_model' => $relatedModel,
                        'method_name' => $rel['method'] ?? Str::camel($related),
                    ];
                    break;

                case 'belongsToMany':
                    $this->relationships[] = [
                        'type' => 'belongsToMany',
                        'related_model' => $relatedModel,
                        'method_name' => $rel['method'] ?? Str::camel(Str::plural($related)),
                        'pivot_table' => $rel['pivot'] ?? null,
                    ];
                    break;

                case 'morphMany':
                    $this->relationships[] = [
                        'type' => 'morphMany',
                        'related_model' => $relatedModel,
                        'method_name' => $rel['method'] ?? Str::camel(Str::plural($related)),
                    ];
                    break;

                case 'morphTo':
                    $this->relationships[] = [
                        'type' => 'morphTo',
                        'related_model' => $relatedModel,
                        'method_name' => $rel['method'] ?? Str::camel($related),
                    ];
                    break;
            }
        }

        $this->info('  → Parsed ' . count($this->relationships) . ' relationships from JSON');
    }
}
