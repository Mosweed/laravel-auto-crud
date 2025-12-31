# Changelog

All notable changes to `laravel-auto-crud` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-01-01

### Added
- Initial release
- CRUD scaffolding generator with `make:crud` command
- Support for API and Web controllers
- Blade views with Tailwind CSS and Bootstrap support
- Livewire components generation
- Automatic route registration in `web.php` and `api.php`
- Custom app layout with navigation (`crud:layout` command)
- Soft deletes support with restore/force-delete functionality
- Filterable trait for query filtering
- Sortable trait for query sorting
- Searchable functionality
- Form Request generation with validation rules
- Policy generation
- API Resource generation
- Migration generation with field definitions
- Factory generation with Faker support
- Seeder generation
- Test generation (Pest and PHPUnit auto-detection)
- JSON configuration for batch CRUD generation
- Configurable Tailwind colors (primary, secondary, danger, success, warning)
- Dark mode support
- Navigation auto-update with `--add-to-nav` option
- Rollback mechanism on generation errors
- Command line field definitions with types and modifiers
- Relationship definitions (belongsTo, hasMany, belongsToMany, hasOne, morphMany, morphTo)
- Schema analysis from existing database tables

### Security
- Input validation in generated Form Requests
- CSRF protection in generated forms
- Authorization via generated Policies
