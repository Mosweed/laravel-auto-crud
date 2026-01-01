<p align="center">
  <img src="https://autocrud.mohmadyazansweed.nl/logo.png" alt="Laravel Auto CRUD" width="100">
</p>

# Laravel Auto CRUD - Complete Documentatie

[![Tests](https://github.com/Mosweed/laravel-auto-crud/actions/workflows/tests.yml/badge.svg)](https://github.com/Mosweed/laravel-auto-crud/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/mosweed/laravel-auto-crud.svg?style=flat-square)](https://packagist.org/packages/mosweed/laravel-auto-crud)
[![License](https://img.shields.io/packagist/l/mosweed/laravel-auto-crud.svg?style=flat-square)](https://packagist.org/packages/mosweed/laravel-auto-crud)

🌐 **Website**: [autocrud.mohmadyazansweed.nl](https://autocrud.mohmadyazansweed.nl)

Een krachtige Laravel package die complete CRUD scaffolding genereert in seconden. Genereer models, controllers, views, routes, tests en meer met één commando.

---

## Inhoudsopgave

1. [Installatie](#installatie)
2. [Quick Start](#quick-start)
3. [Commando's](#commandos)
   - [make:crud](#makecrud)
   - [crud:layout](#crudlayout)
4. [Velden Definiëren](#velden-definiëren)
5. [Relaties](#relaties)
6. [JSON Configuratie](#json-configuratie)
7. [Traits](#traits)
   - [Filterable](#filterable-trait)
   - [Sortable](#sortable-trait)
8. [Views & Styling](#views--styling)
9. [API Ondersteuning](#api-ondersteuning)
10. [Livewire Components](#livewire-components)
11. [Configuratie](#configuratie)
12. [Stubs Aanpassen](#stubs-aanpassen)
13. [Voorbeelden](#voorbeelden)

---

## Installatie

### Vereisten

- PHP 8.2+
- Laravel 11.x of 12.x

### Installeren via Composer

```bash
composer require mosweed/laravel-auto-crud
```

De package registreert automatisch de service provider.

### Layout Publiceren (Aanbevolen)

```bash
php artisan crud:layout
```

### Configuratie Publiceren (Optioneel)

```bash
php artisan vendor:publish --tag=auto-crud-config
```

---

## Quick Start

```bash
# Genereer een complete CRUD
php artisan make:crud Product \
    --fields="name:string,price:decimal,description:text:nullable" \
    --belongsTo=Category \
    --all \
    --add-to-nav

# Voer migraties uit
php artisan migrate

# Build assets
npm run build
```

Je CRUD is nu klaar op `/products`!

---

## Commando's

### make:crud

Het hoofdcommando voor het genereren van CRUD scaffolding.

```bash
php artisan make:crud {ModelName} [opties]
```

#### Alle Opties

| Optie | Beschrijving | Standaard |
|-------|--------------|-----------|
| `--type=` | Output type: `api`, `web`, `both`, `livewire` | `both` |
| `--css=` | CSS framework: `tailwind`, `bootstrap` | `tailwind` |
| `--all` | Genereer migration, factory, seeder en tests | `false` |
| `--force` | Overschrijf bestaande bestanden | `false` |
| `--soft-deletes` | Voeg soft delete ondersteuning toe | `false` |
| `--livewire` | Genereer Livewire components | `false` |
| `--tests` | Genereer feature en unit tests | `false` |
| `--no-policy` | Skip policy generatie | `false` |
| `--no-requests` | Skip form request generatie | `false` |
| `--api-resource` | Genereer API resource | `false` |
| `--fields=` | Definieer velden inline | - |
| `--belongsTo=` | Voeg belongsTo relatie toe (meerdere mogelijk) | - |
| `--hasMany=` | Voeg hasMany relatie toe (meerdere mogelijk) | - |
| `--belongsToMany=` | Voeg belongsToMany relatie toe | - |
| `--table=` | Gebruik bestaande tabel voor schema analyse | - |
| `--json=` | Pad naar JSON configuratiebestand | - |
| `--add-to-nav` | Voeg toe aan navigatie menu | `false` |

#### Gegenereerde Bestanden

```
app/
├── Http/Controllers/
│   ├── ProductController.php          # Web controller
│   └── Api/ProductController.php      # API controller
├── Http/Requests/Product/
│   ├── StoreProductRequest.php        # Validatie voor create
│   └── UpdateProductRequest.php       # Validatie voor update
├── Http/Resources/
│   └── ProductResource.php            # API resource
├── Models/
│   └── Product.php                    # Eloquent model
├── Policies/
│   └── ProductPolicy.php              # Authorization policy
└── Livewire/                          # (als --livewire)
    ├── ProductTable.php
    └── ProductForm.php

database/
├── factories/
│   └── ProductFactory.php             # Model factory
├── migrations/
│   └── xxxx_create_products_table.php # Database migration
└── seeders/
    └── ProductSeeder.php              # Database seeder

resources/views/
└── products/
    ├── index.blade.php                # Overzichtspagina
    ├── create.blade.php               # Aanmaakformulier
    ├── edit.blade.php                 # Bewerkformulier
    └── show.blade.php                 # Detailpagina

tests/
├── Feature/
│   └── ProductTest.php                # Feature tests
└── Unit/
    └── ProductTest.php                # Unit tests
```

---

### crud:layout

Publiceert een kant-en-klare app layout met navigatie.

```bash
php artisan crud:layout [opties]
```

#### Opties

| Optie | Beschrijving |
|-------|--------------|
| `--css=tailwind` | CSS framework (tailwind/bootstrap) |
| `--force` | Overschrijf bestaande layout |
| `--welcome` | Publiceer ook een welkom pagina |
| `--models=Product --models=Category` | Voeg modellen toe aan navigatie |

#### Tailwind v4 (Standaard)

Laravel 12 gebruikt standaard Tailwind v4. De layout command detecteert automatisch je versie:

- **Tailwind v4** (aanbevolen): Kleuren worden toegevoegd aan `resources/css/app.css` onder `@theme`
- **Tailwind v3** (legacy): Configuratie via `tailwind.config.js`

#### Welcome Page

Publiceer een professionele welkom pagina die de app-layout gebruikt:

```bash
# Layout + welcome page samen
php artisan crud:layout --welcome

# Met Bootstrap
php artisan crud:layout --css=bootstrap --welcome

# Forceer overschrijven
php artisan crud:layout --welcome --force
```

De welcome page bevat:
- Hero sectie met gradient en app naam
- Snelle toegang kaarten naar alle CRUD modules
- Aan de slag sectie met documentatie links
- Gebruikt dezelfde app-layout als alle CRUD views

De welcome page wordt opgeslagen in `resources/views/welcome.blade.php`.

---

## Velden Definiëren

### Inline met --fields

```bash
php artisan make:crud Post --fields="title:string,body:text,is_published:boolean"
```

### Formaat

```
veldnaam:type:modifier1:modifier2
```

### Ondersteunde Types

| Type | Database Kolom | Voorbeeld |
|------|----------------|-----------|
| `string` | VARCHAR(255) | `name:string` |
| `text` | TEXT | `body:text` |
| `integer` / `int` | INTEGER | `count:integer` |
| `bigInteger` | BIGINT | `views:bigInteger` |
| `boolean` / `bool` | BOOLEAN | `is_active:boolean` |
| `date` | DATE | `birth_date:date` |
| `datetime` | DATETIME | `published_at:datetime` |
| `time` | TIME | `start_time:time` |
| `decimal` | DECIMAL | `price:decimal` |
| `float` / `double` | FLOAT | `rating:float` |
| `json` | JSON | `metadata:json` |
| `foreignId` | BIGINT UNSIGNED | `user_id:foreignId` |

### Modifiers

| Modifier | Beschrijving | Voorbeeld |
|----------|--------------|-----------|
| `nullable` | Veld mag NULL zijn | `bio:text:nullable` |
| `unique` | Unieke constraint | `email:string:unique` |
| `255` | String lengte | `title:string:100` |

### Voorbeelden

```bash
# Basis velden
--fields="name:string,email:string:unique,bio:text:nullable"

# Met lengte
--fields="title:string:100,slug:string:unique"

# Foreign keys (auto-detecteert belongsTo)
--fields="name:string,category_id:foreignId,user_id:foreignId"

# Decimal met precisie
--fields="price:decimal:8,2"
```

---

## Relaties

### BelongsTo

```bash
php artisan make:crud Post --belongsTo=User --belongsTo=Category
```

Genereert:
```php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public function category(): BelongsTo
{
    return $this->belongsTo(Category::class);
}
```

### HasMany

```bash
php artisan make:crud User --hasMany=Post --hasMany=Comment
```

Genereert:
```php
public function posts(): HasMany
{
    return $this->hasMany(Post::class);
}

public function comments(): HasMany
{
    return $this->hasMany(Comment::class);
}
```

### BelongsToMany

```bash
php artisan make:crud Post --belongsToMany=Tag
```

Genereert:
```php
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class);
}
```

### Combineren

```bash
php artisan make:crud Product \
    --fields="name:string,price:decimal" \
    --belongsTo=Category \
    --belongsTo=Brand \
    --hasMany=Review \
    --belongsToMany=Tag \
    --all
```

---

## JSON Configuratie

Voor complexe projecten kun je meerdere CRUDs definiëren in een JSON bestand.

### crud.json Voorbeeld

```json
{
    "models": [
        {
            "name": "Category",
            "fields": [
                {"name": "name", "type": "string"},
                {"name": "slug", "type": "string", "unique": true},
                {"name": "description", "type": "text", "nullable": true}
            ],
            "relationships": [
                {"type": "hasMany", "model": "Product"}
            ]
        },
        {
            "name": "Product",
            "fields": [
                {"name": "name", "type": "string"},
                {"name": "slug", "type": "string", "unique": true},
                {"name": "description", "type": "text", "nullable": true},
                {"name": "price", "type": "decimal"},
                {"name": "stock", "type": "integer", "default": 0},
                {"name": "is_active", "type": "boolean", "default": true}
            ],
            "relationships": [
                {"type": "belongsTo", "model": "Category"},
                {"type": "belongsTo", "model": "Brand"},
                {"type": "belongsToMany", "model": "Tag"},
                {"type": "hasMany", "model": "Review"}
            ],
            "options": {
                "softDeletes": true
            }
        },
        {
            "name": "Brand",
            "fields": [
                {"name": "name", "type": "string"},
                {"name": "logo", "type": "string", "nullable": true}
            ]
        },
        {
            "name": "Tag",
            "fields": [
                {"name": "name", "type": "string"},
                {"name": "color", "type": "string", "default": "#3b82f6"}
            ]
        },
        {
            "name": "Review",
            "fields": [
                {"name": "rating", "type": "integer"},
                {"name": "comment", "type": "text", "nullable": true}
            ],
            "relationships": [
                {"type": "belongsTo", "model": "Product"},
                {"type": "belongsTo", "model": "User"}
            ]
        }
    ],
    "options": {
        "all": true,
        "css": "tailwind",
        "type": "both"
    }
}
```

### Uitvoeren

```bash
php artisan make:crud --json=crud.json
```

### Relatie Types in JSON

| Type | Beschrijving |
|------|--------------|
| `belongsTo` | Many-to-one relatie |
| `hasMany` | One-to-many relatie |
| `hasOne` | One-to-one relatie |
| `belongsToMany` | Many-to-many relatie |
| `morphMany` | Polymorphic one-to-many |
| `morphTo` | Polymorphic inverse |

---

## Traits

### Filterable Trait

De `Filterable` trait voegt krachtige filter mogelijkheden toe aan je models.

#### Gebruik in Model

```php
use AutoCrud\Traits\Filterable;

class Product extends Model
{
    use Filterable;

    // Optioneel: beperk filterable velden
    protected array $filterable = ['name', 'price', 'category_id'];
}
```

#### Filter Queries

```php
// In controller
$products = Product::filter($request->all())->get();
```

#### URL Parameters

| Parameter | Beschrijving | Voorbeeld |
|-----------|--------------|-----------|
| `?field=value` | Exacte match | `?status=active` |
| `?field=*value*` | LIKE search met wildcards | `?name=*phone*` |
| `?field_from=x` | Groter dan of gelijk | `?price_from=100` |
| `?field_to=y` | Kleiner dan of gelijk | `?price_to=500` |
| `?field[]=a&field[]=b` | IN query (array) | `?status[]=active&status[]=pending` |
| `?field_not=value` | Niet gelijk aan | `?status_not=deleted` |
| `?field_null=1` | IS NULL | `?deleted_at_null=1` |
| `?field_not_null=1` | IS NOT NULL | `?published_at_not_null=1` |

#### Search Scope

```php
// Zoek in meerdere velden
$products = Product::search('iphone', ['name', 'description'])->get();

// Of met request
$products = Product::filter(['search' => 'iphone'])->get();
```

---

### Sortable Trait

De `Sortable` trait voegt sorteer mogelijkheden toe.

#### Gebruik in Model

```php
use AutoCrud\Traits\Sortable;

class Product extends Model
{
    use Sortable;

    // Optioneel: beperk sortable velden
    protected array $sortable = ['name', 'price', 'created_at'];

    // Optioneel: standaard sortering
    protected array $defaultSort = [
        'field' => 'name',
        'direction' => 'asc'
    ];
}
```

#### Sort Queries

```php
// Enkelvoudige sortering
$products = Product::sort('name', 'asc')->get();

// Meervoudige sortering
$products = Product::sort(['category_id', 'name'], ['asc', 'asc'])->get();

// Vanuit request
$products = Product::sortFromRequest($request)->get();
```

#### URL Parameters

```
?sort=name&direction=asc
?sort[]=category_id&sort[]=name&direction[]=asc&direction[]=asc
```

#### Combineren met Filterable

```php
$products = Product::query()
    ->filter($request->all())
    ->sortFromRequest($request)
    ->paginate(15);
```

---

## Views & Styling

### CSS Frameworks

De package ondersteunt twee CSS frameworks:

- **Tailwind CSS** (standaard) - Met dark mode ondersteuning
- **Bootstrap 5** - Klassieke Bootstrap styling

```bash
# Tailwind (standaard)
php artisan make:crud Product --css=tailwind

# Bootstrap
php artisan make:crud Product --css=bootstrap
```

### View Features

Alle gegenereerde views bevatten:

- **Modern design** met gradient knoppen en iconen
- **Responsief** - Werkt op desktop en mobiel
- **Dark mode** - Automatische ondersteuning (Tailwind)
- **SVG iconen** - Heroicons geïntegreerd
- **Flash messages** - Success/error notificaties
- **Lege staat** - Mooie weergave als er geen data is
- **Paginatie** - Met resultaat tellers

### Kleur Aanpassing

#### Tailwind v4 (Standaard - Laravel 12)

Bewerk `resources/css/app.css`:

```css
@theme {
    /* Primary - Verander naar groen */
    --color-primary-50: #f0fdf4;
    --color-primary-100: #dcfce7;
    --color-primary-200: #bbf7d0;
    --color-primary-300: #86efac;
    --color-primary-400: #4ade80;
    --color-primary-500: #22c55e;
    --color-primary-600: #16a34a;
    --color-primary-700: #15803d;
    --color-primary-800: #166534;
    --color-primary-900: #14532d;
    --color-primary-950: #052e16;

    /* Secondary, Danger, Success, Warning... */
}
```

#### Tailwind v3 (Legacy)

Voor oudere projecten met Tailwind v3, bewerk `tailwind.config.js`:

```javascript
import colors from 'tailwindcss/colors';

export default {
    theme: {
        extend: {
            colors: {
                primary: colors.emerald,    // Groen thema
                secondary: colors.teal,
                danger: colors.red,
                success: colors.green,
                warning: colors.amber,
            },
        },
    },
};
```

### Beschikbare Kleuren

| Kleur | Gebruik |
|-------|---------|
| `primary` | Hoofd acties, links, accenten |
| `secondary` | Secundaire acties, bewerken |
| `danger` | Verwijderen, fouten |
| `success` | Succes meldingen, bevestigingen |
| `warning` | Waarschuwingen, let op |

---

## API Ondersteuning

### API Controllers

Gegenereerd in `app/Http/Controllers/Api/`:

```php
class ProductController extends Controller
{
    public function index()
    {
        return ProductResource::collection(
            Product::filter(request()->all())
                ->sortFromRequest(request())
                ->paginate()
        );
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());
        return new ProductResource($product);
    }

    public function show(Product $product)
    {
        return new ProductResource($product->load(['category', 'tags']));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());
        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->noContent();
    }
}
```

### API Resources

```php
class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### Routes

Automatisch toegevoegd aan `routes/api.php`:

```php
Route::apiResource('products', Api\ProductController::class);
```

### Endpoints

| Methode | URI | Actie |
|---------|-----|-------|
| GET | `/api/products` | Lijst alle producten |
| POST | `/api/products` | Maak nieuw product |
| GET | `/api/products/{id}` | Toon product |
| PUT/PATCH | `/api/products/{id}` | Update product |
| DELETE | `/api/products/{id}` | Verwijder product |

### Soft Deletes Endpoints

Als `--soft-deletes` is gebruikt:

| Methode | URI | Actie |
|---------|-----|-------|
| POST | `/api/products/{id}/restore` | Herstel product |
| DELETE | `/api/products/{id}/force` | Permanent verwijderen |

---

## Livewire Components

### Genereren

```bash
php artisan make:crud Product --livewire
```

### Gegenereerde Components

**ProductTable.php** - Datatable met:
- Zoeken
- Sorteren
- Paginatie
- Inline delete

**ProductForm.php** - Formulier met:
- Real-time validatie
- Create/Edit modes
- Relatie selecties

### Views

```
resources/views/livewire/
├── product-table.blade.php
└── product-form.blade.php
```

### Gebruik

```blade
{{-- In een Blade view --}}
<livewire:product-table />

<livewire:product-form :product="$product" />
```

---

## Configuratie

Na publiceren (`php artisan vendor:publish --tag=auto-crud-config`):

```php
// config/auto-crud.php

return [
    // Standaard output type
    'default_type' => 'both', // api, web, both, livewire

    // Standaard CSS framework
    'default_css' => 'tailwind', // tailwind, bootstrap

    // Namespaces
    'namespaces' => [
        'models' => 'App\\Models',
        'controllers' => 'App\\Http\\Controllers',
        'api_controllers' => 'App\\Http\\Controllers\\Api',
        'requests' => 'App\\Http\\Requests',
        'policies' => 'App\\Policies',
        'resources' => 'App\\Http\\Resources',
        'livewire' => 'App\\Livewire',
    ],

    // Bestandspaden
    'paths' => [
        'models' => app_path('Models'),
        'controllers' => app_path('Http/Controllers'),
        'views' => resource_path('views'),
        // ...
    ],

    // Middleware
    'middleware' => [
        'web' => ['web', 'auth'],
        'api' => ['api', 'auth:sanctum'],
    ],

    // Paginatie
    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],

    // Standaard timestamps
    'timestamps' => true,

    // Standaard soft deletes
    'soft_deletes' => false,

    // Filterable/Sortable ('*' voor alles)
    'filterable' => '*',
    'sortable' => '*',

    // Standaard sortering
    'default_sort' => [
        'field' => 'created_at',
        'direction' => 'desc',
    ],

    // Validatie mapping
    'validation_mapping' => [
        'string' => 'string|max:255',
        'text' => 'string',
        'integer' => 'integer',
        'boolean' => 'boolean',
        'date' => 'date',
        'email' => 'email|max:255',
        // ...
    ],
];
```

---

## Stubs Aanpassen

### Publiceren

```bash
php artisan vendor:publish --tag=auto-crud-stubs
```

Stubs worden gepubliceerd naar `stubs/auto-crud/`.

### Structuur

```
stubs/auto-crud/
├── controller.stub
├── controller.api.stub
├── model.stub
├── migration.stub
├── factory.stub
├── seeder.stub
├── policy.stub
├── request.store.stub
├── request.update.stub
├── resource.stub
├── views/
│   ├── tailwind/
│   │   ├── index.blade.stub
│   │   ├── create.blade.stub
│   │   ├── edit.blade.stub
│   │   ├── show.blade.stub
│   │   └── welcome.blade.stub
│   └── bootstrap/
│       ├── index.blade.stub
│       └── ...
└── livewire/
    ├── table.stub
    └── form.stub
```

### Placeholders

| Placeholder | Beschrijving |
|-------------|--------------|
| `{{ model }}` | Model naam (Product) |
| `{{ modelLower }}` | Lowercase (product) |
| `{{ modelVariable }}` | Camel case ($product) |
| `{{ modelVariablePlural }}` | Plural camel ($products) |
| `{{ modelPlural }}` | Plural (Products) |
| `{{ modelPluralLower }}` | Plural lower (products) |
| `{{ modelKebab }}` | Kebab case (product) |
| `{{ table }}` | Tabel naam (products) |
| `{{ routeName }}` | Route naam (products) |
| `{{ viewPath }}` | View pad (products) |

---

## Voorbeelden

### E-commerce Setup

```bash
# 1. Layout publiceren
php artisan crud:layout --welcome

# 2. Categories
php artisan make:crud Category \
    --fields="name:string,slug:string:unique,description:text:nullable,image:string:nullable" \
    --hasMany=Product \
    --all \
    --add-to-nav

# 3. Brands
php artisan make:crud Brand \
    --fields="name:string,logo:string:nullable,website:string:nullable" \
    --hasMany=Product \
    --all \
    --add-to-nav

# 4. Products
php artisan make:crud Product \
    --fields="name:string,slug:string:unique,description:text,price:decimal,stock:integer,is_active:boolean" \
    --belongsTo=Category \
    --belongsTo=Brand \
    --belongsToMany=Tag \
    --soft-deletes \
    --all \
    --add-to-nav

# 5. Tags
php artisan make:crud Tag \
    --fields="name:string,slug:string:unique,color:string" \
    --belongsToMany=Product \
    --all \
    --add-to-nav

# 6. Migraties uitvoeren
php artisan migrate

# 7. Assets builden
npm run build
```

### Blog Setup

```bash
php artisan make:crud Post \
    --fields="title:string,slug:string:unique,excerpt:text:nullable,body:text,is_published:boolean,published_at:datetime:nullable" \
    --belongsTo=User \
    --belongsTo=Category \
    --belongsToMany=Tag \
    --hasMany=Comment \
    --soft-deletes \
    --all

php artisan make:crud Comment \
    --fields="body:text,is_approved:boolean" \
    --belongsTo=Post \
    --belongsTo=User \
    --all
```

### API-Only Project

```bash
php artisan make:crud Product \
    --type=api \
    --fields="name:string,price:decimal" \
    --api-resource \
    --all
```

### Livewire Admin Panel

```bash
php artisan make:crud User \
    --livewire \
    --fields="name:string,email:string:unique,role:string" \
    --all

php artisan make:crud Setting \
    --livewire \
    --fields="key:string:unique,value:text,type:string" \
    --all
```

---

## Rollback bij Fouten

Als er een fout optreedt tijdens generatie, worden alle gemaakte bestanden automatisch verwijderd:

```
Generating CRUD for Product...

  Generating Model... ✓
  Generating Controller(s)... ✓
  Generating Form Requests... ✓
  Generating Policy... ✗ FAILED

An error occurred: [error message]

Rolling back created files...
  ✗ Deleted: app/Models/Product.php
  ✗ Deleted: app/Http/Controllers/ProductController.php
  ✗ Deleted: app/Http/Requests/Product/StoreProductRequest.php
  ✗ Deleted: app/Http/Requests/Product/UpdateProductRequest.php

Rollback completed. 4 files removed.
```

---

## Testing

De package bevat tests voor alle functionaliteit:

```bash
# Alle tests uitvoeren
composer test

# Met coverage
composer test-coverage
```

### Gegenereerde Tests

Als je `--tests` of `--all` gebruikt:

```php
// tests/Feature/ProductTest.php
class ProductTest extends TestCase
{
    public function test_can_list_products()
    {
        $response = $this->get(route('products.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_product()
    {
        $response = $this->post(route('products.store'), [
            'name' => 'Test Product',
            'price' => 99.99,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', ['name' => 'Test Product']);
    }

    // ... meer tests
}
```

---

## Changelog

Zie [CHANGELOG.md](CHANGELOG.md) voor recente wijzigingen.

## Contributing

Bijdragen zijn welkom! Open een Pull Request op GitHub.

## Security

Vind je een beveiligingsprobleem? Open een issue op GitHub.

## Credits

- [Mosweed](https://github.com/Mosweed)

## License

MIT License. Zie [LICENSE](LICENSE) voor details.
