<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Output Type
    |--------------------------------------------------------------------------
    |
    | Determines the default output type when running make:crud without
    | the --type option. Options: 'api', 'web', 'both', 'livewire'
    |
    */
    'default_type' => 'both',

    /*
    |--------------------------------------------------------------------------
    | Default CSS Framework
    |--------------------------------------------------------------------------
    |
    | The CSS framework to use for generated views.
    | Options: 'tailwind', 'bootstrap'
    |
    */
    'default_css' => 'tailwind',

    /*
    |--------------------------------------------------------------------------
    | Namespaces
    |--------------------------------------------------------------------------
    |
    | Configure the namespaces for generated files.
    |
    */
    'namespaces' => [
        'models' => 'App\\Models',
        'controllers' => 'App\\Http\\Controllers',
        'api_controllers' => 'App\\Http\\Controllers\\Api',
        'requests' => 'App\\Http\\Requests',
        'policies' => 'App\\Policies',
        'resources' => 'App\\Http\\Resources',
        'livewire' => 'App\\Livewire',
    ],

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | Configure the paths for generated files.
    |
    */
    'paths' => [
        'models' => app_path('Models'),
        'controllers' => app_path('Http/Controllers'),
        'api_controllers' => app_path('Http/Controllers/Api'),
        'requests' => app_path('Http/Requests'),
        'policies' => app_path('Policies'),
        'resources' => app_path('Http/Resources'),
        'views' => resource_path('views'),
        'livewire' => app_path('Livewire'),
        'livewire_views' => resource_path('views/livewire'),
        'migrations' => database_path('migrations'),
        'factories' => database_path('factories'),
        'seeders' => database_path('seeders'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stub Path
    |--------------------------------------------------------------------------
    |
    | The path to custom stubs. If published, custom stubs will be used
    | instead of the package defaults.
    |
    */
    'stub_path' => base_path('stubs/auto-crud'),

    /*
    |--------------------------------------------------------------------------
    | Default Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to generated routes.
    |
    */
    'middleware' => [
        'web' => ['web', 'auth'],
        'api' => ['api', 'auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings.
    |
    */
    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Timestamps
    |--------------------------------------------------------------------------
    |
    | Whether to include timestamps in migrations by default.
    |
    */
    'timestamps' => true,

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | Whether to include soft deletes by default.
    |
    */
    'soft_deletes' => false,

    /*
    |--------------------------------------------------------------------------
    | Filterable Fields
    |--------------------------------------------------------------------------
    |
    | Default fields that should be filterable. Use '*' for all fields.
    |
    */
    'filterable' => '*',

    /*
    |--------------------------------------------------------------------------
    | Sortable Fields
    |--------------------------------------------------------------------------
    |
    | Default fields that should be sortable. Use '*' for all fields.
    |
    */
    'sortable' => '*',

    /*
    |--------------------------------------------------------------------------
    | Default Sort
    |--------------------------------------------------------------------------
    |
    | Default sorting configuration.
    |
    */
    'default_sort' => [
        'field' => 'created_at',
        'direction' => 'desc',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules Mapping
    |--------------------------------------------------------------------------
    |
    | Map database column types to validation rules.
    |
    */
    'validation_mapping' => [
        'string' => 'string|max:255',
        'text' => 'string',
        'integer' => 'integer',
        'bigint' => 'integer',
        'float' => 'numeric',
        'decimal' => 'numeric',
        'boolean' => 'boolean',
        'date' => 'date',
        'datetime' => 'date',
        'timestamp' => 'date',
        'json' => 'array',
        'uuid' => 'uuid',
        'email' => 'email|max:255',
    ],

];
