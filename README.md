# Laravel Auto CRUD

[![Tests](https://github.com/Mosweed/laravel-auto-crud/actions/workflows/tests.yml/badge.svg)](https://github.com/Mosweed/laravel-auto-crud/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/mosweed/laravel-auto-crud.svg?style=flat-square)](https://packagist.org/packages/mosweed/laravel-auto-crud)
[![Total Downloads](https://img.shields.io/packagist/dt/mosweed/laravel-auto-crud.svg?style=flat-square)](https://packagist.org/packages/mosweed/laravel-auto-crud)
[![License](https://img.shields.io/packagist/l/mosweed/laravel-auto-crud.svg?style=flat-square)](https://packagist.org/packages/mosweed/laravel-auto-crud)
[![GitHub Stars](https://img.shields.io/github/stars/Mosweed/laravel-auto-crud.svg?style=flat-square)](https://github.com/Mosweed/laravel-auto-crud)

A powerful Laravel package that generates complete CRUD scaffolding in seconds. Generate models, controllers, views, routes, tests, and more with a single command.

## Features

- **Complete CRUD Scaffolding** - Models, Controllers, Views, Routes, all in one command
- **API & Web Support** - Generate API controllers and Web controllers simultaneously
- **Blade Views** - Tailwind CSS and Bootstrap support with dark mode
- **Livewire Components** - Real-time table and form components
- **Automatic Routing** - Routes are automatically added to your route files
- **Custom App Layout** - Publish a ready-to-use layout with navigation
- **Configurable Colors** - Easy Tailwind color customization
- **Soft Deletes** - Full soft delete support with restore/force-delete
- **Filterable & Sortable** - Built-in query traits for filtering and sorting
- **Test Generation** - Automatic Pest/PHPUnit test generation
- **JSON Configuration** - Batch generate multiple CRUDs from a config file
- **Rollback on Error** - Automatic cleanup if generation fails

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

## Installation

Install the package via Composer:

```bash
composer require mosweed/laravel-auto-crud
```

The package will automatically register its service provider.

### Publish the Layout (Recommended)

```bash
php artisan crud:layout
```

### Configure Tailwind Colors

The `crud:layout` command automatically detects your Tailwind version and configures colors accordingly.

#### Tailwind v4 (Laravel 12 default)

Colors are configured in `resources/css/app.css` under `@theme`:

```css
@theme {
    /* Primary - Blue */
    --color-primary-500: #3b82f6;
    --color-primary-600: #2563eb;
    --color-primary-700: #1d4ed8;
    /* ... more shades */

    /* Change to green theme */
    --color-primary-500: #22c55e;
    --color-primary-600: #16a34a;
}
```

#### Tailwind v3

Add colors to `tailwind.config.js`:

```javascript
import colors from 'tailwindcss/colors';

export default {
    theme: {
        extend: {
            colors: {
                primary: colors.blue,
                secondary: colors.indigo,
                danger: colors.red,
                success: colors.green,
                warning: colors.amber,
            },
        },
    },
};
```

### Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=auto-crud-config
```

## Quick Start

```bash
# Generate a complete CRUD
php artisan make:crud Product \
    --fields="name:string,price:decimal,description:text:nullable" \
    --belongsTo=Category \
    --all \
    --add-to-nav

# Run migrations
php artisan migrate

# Build assets
npm run build
```

That's it! Your CRUD is ready at `/products`.

## Usage

### Basic Command

```bash
php artisan make:crud Post
```

This generates:
- Model with Filterable and Sortable traits
- Web Controller
- API Controller
- Form Requests (Store & Update)
- Policy
- Blade Views (index, create, edit, show)
- Routes in `web.php` and `api.php`

### Command Options

| Option | Description |
|--------|-------------|
| `--type=api\|web\|both` | Output type (default: both) |
| `--css=tailwind\|bootstrap` | CSS framework (default: tailwind) |
| `--all` | Generate migration, factory, seeder, and tests |
| `--force` | Overwrite existing files |
| `--soft-deletes` | Add soft delete support |
| `--livewire` | Generate Livewire components |
| `--tests` | Generate feature and unit tests |
| `--fields=...` | Define fields inline |
| `--belongsTo=Model` | Add belongsTo relationship |
| `--hasMany=Model` | Add hasMany relationship |
| `--belongsToMany=Model` | Add belongsToMany relationship |
| `--json=path` | Generate from JSON configuration |
| `--add-to-nav` | Add to navigation menu |

### Defining Fields

```bash
php artisan make:crud Post --fields="title:string,body:text,is_published:boolean"
```

#### Supported Types

| Type | Database Column |
|------|-----------------|
| `string` | VARCHAR |
| `text` | TEXT |
| `integer` | INTEGER |
| `boolean` | BOOLEAN |
| `date` | DATE |
| `datetime` | DATETIME |
| `decimal` | DECIMAL |
| `json` | JSON |
| `foreignId` | BIGINT UNSIGNED |

#### Modifiers

```bash
--fields="title:string:255,slug:string:unique,body:text:nullable"
```

- `nullable` - Field can be null
- `unique` - Add unique constraint
- `255` - String length

### Relationships

```bash
# BelongsTo
php artisan make:crud Post --belongsTo=User --belongsTo=Category

# HasMany
php artisan make:crud User --hasMany=Post

# BelongsToMany
php artisan make:crud Post --belongsToMany=Tag
```

### JSON Configuration

Create a `crud.json` file:

```json
{
    "models": [
        {
            "name": "Product",
            "fields": [
                {"name": "name", "type": "string"},
                {"name": "price", "type": "decimal"}
            ],
            "relationships": [
                {"type": "belongsTo", "model": "Category"}
            ]
        },
        {
            "name": "Category",
            "fields": [
                {"name": "name", "type": "string"}
            ]
        }
    ],
    "options": {
        "all": true,
        "softDeletes": true
    }
}
```

Run:
```bash
php artisan make:crud --json=crud.json
```

## Layout Command

Publish a ready-to-use app layout with navigation:

```bash
php artisan crud:layout
```

Options:
- `--css=bootstrap` - Use Bootstrap instead of Tailwind
- `--force` - Overwrite existing layout
- `--models=Product --models=Category` - Pre-populate navigation

## Filtering & Sorting

The generated models include `Filterable` and `Sortable` traits:

```php
// Filter
GET /products?status=active
GET /products?price_from=10&price_to=100
GET /products?search=keyword

// Sort
GET /products?sort=name&direction=asc

// Soft Deletes
GET /products?trashed=1
GET /products?trashed=only
```

## Color Customization

All views use semantic color names (primary, secondary, danger, success, warning).

### Tailwind v4

Edit `resources/css/app.css`:

```css
@theme {
    --color-primary-500: #10b981;  /* Emerald/Green theme */
    --color-primary-600: #059669;
    --color-secondary-500: #14b8a6; /* Teal */
    --color-secondary-600: #0d9488;
}
```

### Tailwind v3

Edit `tailwind.config.js`:

```javascript
colors: {
    primary: colors.emerald,
    secondary: colors.teal,
}
```

Run `npm run build` after changes.

## Generated Files

```
app/
├── Http/Controllers/
│   ├── ProductController.php
│   └── Api/ProductController.php
├── Http/Requests/Product/
│   ├── StoreProductRequest.php
│   └── UpdateProductRequest.php
├── Models/Product.php
└── Policies/ProductPolicy.php

database/
├── factories/ProductFactory.php
├── migrations/xxxx_create_products_table.php
└── seeders/ProductSeeder.php

resources/views/
├── components/app-layout.blade.php
└── products/
    ├── index.blade.php
    ├── create.blade.php
    ├── edit.blade.php
    └── show.blade.php

routes/
├── web.php (routes added)
└── api.php (routes added)

tests/
├── Feature/ProductTest.php
└── Unit/ProductTest.php
```

## Publishing Assets

```bash
# Configuration
php artisan vendor:publish --tag=auto-crud-config

# Stubs (for customization)
php artisan vendor:publish --tag=auto-crud-stubs

# Tailwind layout only
php artisan vendor:publish --tag=auto-crud-layout-tailwind

# Bootstrap layout only
php artisan vendor:publish --tag=auto-crud-layout-bootstrap
```

## Configuration

After publishing, configure in `config/auto-crud.php`:

- Default output type (api/web/both)
- Default CSS framework
- Controller namespaces
- Middleware settings
- Pagination defaults
- Validation rules mapping

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security

If you discover any security-related issues, please open an issue on GitHub.

## Credits

- [Mosweed](https://github.com/Mosweed)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
