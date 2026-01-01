# Changelog

Alle belangrijke wijzigingen aan dit project worden gedocumenteerd in dit bestand.

Het formaat is gebaseerd op [Keep a Changelog](https://keepachangelog.com/nl/1.0.0/),
en dit project volgt [Semantic Versioning](https://semver.org/lang/nl/).

## [1.0.3] - 2026-01-01

### Added
- Complete DOCUMENTATION.md met uitgebreide package documentatie
- Logo en website badges in README en DOCUMENTATION
- Dark mode friendly README design met gecentreerde branding

### Changed
- Welcome page templates gebruiken nu `<x-app-layout>` (Tailwind) en `@extends('layouts.app')` (Bootstrap)
- Tailwind v4 gemarkeerd als standaard in documentatie (v3 als legacy)
- README volledig herschreven met modern dark mode ontwerp
- Verbeterde API route registratie - controleert nu altijd bootstrap/app.php

### Fixed
- API routes worden nu betrouwbaar geregistreerd in bootstrap/app.php voor Laravel 11+

---

## [1.0.2] - 2026-01-01

### Added
- Welcome page generator met `--welcome` optie voor `crud:layout` commando
- Nieuwe `WelcomeGenerator` class voor professionele welkom pagina's
- Quick links naar alle CRUD modules op de welkom pagina
- `{{ modelLower }}` placeholder in BaseGenerator voor lowercase model namen

### Changed
- **Volledig vernieuwde view styling** voor alle CRUD pagina's
- SVG iconen toegevoegd aan headers, knoppen en lege staten
- Gradient knoppen met hover effecten (Tailwind)
- Gekleurde icoon badges en schaduw kaarten (Bootstrap)
- Verbeterde lege staat design met call-to-action
- Paginatie met resultaat tellers ("Toont 1-10 van 50 resultaten")
- Annuleer links en terug knoppen met iconen
- Afgeronde hoeken en moderne kaart styling

---

## [1.1.0] - 2026-01-01

### Added
- **Tailwind v4 ondersteuning** met automatische versie detectie
- CSS theme injection in `resources/css/app.css` voor Tailwind v4
- `injectTailwindTheme()` methode in LayoutGenerator
- `detectTailwindVersion()` methode voor automatische detectie
- Automatische `api.php` aanmaak als deze niet bestaat
- API routes registratie in `bootstrap/app.php` voor Laravel 11+
- Semantische kleuren (primary, secondary, danger, success, warning)
- GitHub Actions CI/CD workflows
  - `tests.yml` - PHPUnit tests op PHP 8.2/8.3/8.4 met Laravel 11 & 12
  - `code-style.yml` - PHP CS Fixer code style checking
  - `static-analysis.yml` - PHPStan level 5 analyse
  - `release.yml` - Automatische releases bij tags
- Issue templates voor bugs en feature requests
- Pull request template
- Test infrastructure met Orchestra Testbench
- `FieldParser` utility class

### Changed
- Verwijderd: Alle `$this->authorize()` calls uit gegenereerde code
- Auth secties in layouts zijn nu standaard uitgecommentarieerd
- Verbeterde kleur configuratie instructies voor v3 en v4

### Fixed
- Tailwind CSS werkt nu correct in nieuwe Laravel 12 projecten
- API routes worden correct geregistreerd in Laravel 11+ bootstrap

---

## [1.0.0] - 2025-12-31

### Added

#### Core Features
- `make:crud` Artisan commando voor complete CRUD scaffolding
- `crud:layout` Artisan commando voor app layout publicatie
- Model generator met Filterable en Sortable traits
- Controller generator (Web & API)
- View generator (Tailwind & Bootstrap)
- Migration generator met automatische field parsing
- Factory generator met Faker integratie
- Seeder generator
- Policy generator
- Form Request generator met automatische validatie regels
- API Resource generator
- Route generator met automatische registratie
- Test generator (Feature & Unit)

#### Relationships
- BelongsTo relaties met automatische foreign key detectie
- HasMany relaties
- HasOne relaties
- BelongsToMany relaties met pivot tabellen
- MorphMany polymorphic relaties
- MorphTo polymorphic inverse relaties

#### Field Types
- String, Text, Integer, BigInteger
- Boolean, Date, DateTime, Time
- Decimal, Float, JSON
- ForeignId met automatische relatie detectie

#### Traits
- `Filterable` trait met geavanceerde query filters
  - Exacte match filtering
  - LIKE queries met wildcards
  - Range queries (from/to)
  - Array/IN queries
  - NULL checks
  - NOT queries
- `Sortable` trait met sortering
  - Enkelvoudige en meervoudige sortering
  - Request-based sortering
  - Configureerbare standaard sortering

#### Livewire Support
- Livewire table component met zoeken, sorteren, paginatie
- Livewire form component met real-time validatie

#### Configuration
- Publiceerbare config file
- Publiceerbare stubs voor aanpassing
- Configureerbare namespaces en paths
- Configureerbare middleware
- Configureerbare paginatie
- Validatie mapping

#### Developer Experience
- JSON configuratie voor batch generatie
- `--add-to-nav` optie voor automatische navigatie integratie
- Schema analyse voor bestaande tabellen
- Automatische rollback bij fouten
- Soft deletes ondersteuning met restore/force-delete

### Security
- Input validatie in gegenereerde Form Requests
- CSRF protectie in gegenereerde formulieren
- Autorisatie via gegenereerde Policies

---

[Unreleased]: https://github.com/Mosweed/laravel-auto-crud/compare/v1.0.2...HEAD
[1.0.2]: https://github.com/Mosweed/laravel-auto-crud/compare/v1.1.0...v1.0.2
[1.0.1]: https://github.com/Mosweed/laravel-auto-crud/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/Mosweed/laravel-auto-crud/releases/tag/v1.0.0
