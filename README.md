# University Website Management System

Modular website platform for universities: every feature is an installable module, and the
public website is a replaceable theme. Built for Daffodil International University, designed to be
reused by other universities.

**Stack:** Laravel 13 · Filament 5 · Livewire 4 · nwidart/laravel-modules · Filament Shield ·
MySQL 8 · Redis · Pest

## Documentation

All design documents are in [`docs/`](docs/) (Bangla with English technical terms).
Start with [`docs/00-HANDOFF.md`](docs/00-HANDOFF.md).

| Step | Document |
|---|---|
| 1 | [BRD](docs/01-BRD.md) |
| 2 | [SRS](docs/02-SRS/00-SRS-Overview.md) |
| 3 | [SAD](docs/03-SAD/01-SAD-PartA-Foundation.md) |
| 4 | [Database Design](docs/04-DB-Design/01-DB-Core-Kernel.md) |
| 5 | [UI/UX](docs/05-UIUX/01-UIUX-PartA-Admin.md) |
| 6 | [API Specification](docs/06-API/01-API-Specification.md) (Release 2) |
| 7 | [Module](docs/07-DevGuide/01-Module-Development-Guide.md) and [Theme](docs/07-DevGuide/02-Theme-Development-Guide.md) Development Guide |
| 8 | [Test Plan](docs/08-Test/01-Test-Plan.md) |
| 9 | [Deployment](docs/09-Deployment/01-Deployment-Plan.md) and [Data Migration](docs/09-Deployment/02-Data-Migration-Plan.md) |
| 10 | [User Manual](docs/10-User-Manual/01-Manual-PartA-Basics.md) |

## Code layout

```
platform/     Platform kernel (namespace Platform\) — scope engine, permissions, module services
Modules/      nwidart modules (core and installable)
app/          Laravel application (panel provider, user model)
stubs/        Overridden package stubs (Shield policies extend Platform\Policies\ScopedPolicy)
tests/        Pest tests
```

## Local setup

Requirements: PHP 8.4, Composer 2, MySQL 8, Node 26 (`.nvmrc`; `nvm use`).

```bash
composer install
cp .env.example .env && php artisan key:generate
# set DB_* in .env, create databases `university` and `university_test` (utf8mb4)
php artisan migrate
php artisan db:seed        # permissions + first Super Admin (SUPER_ADMIN_* in .env;
                           # empty password → random one printed once)
php artisan serve          # admin panel: http://localhost:8000/admin
./vendor/bin/pest          # tests use the university_test database
```
