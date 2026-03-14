# Empet Backend — Claude Guidelines

## Project Overview

API-only Laravel 12 backend with PostgreSQL + PostGIS, Redis, and Sanctum authentication.

## Tech Stack

- PHP 8.4
- Laravel 12
- Laravel Sanctum (API token authentication)
- PostgreSQL 16 + PostGIS 3.4
- Redis 7 (cache, queue, session)
- PHPUnit 11

## Laravel Boost

- Use `search-docs` for version-specific documentation before making changes
- Use `list-artisan-commands` to check available parameters
- Use `tinker` tool for debugging or Eloquent queries
- Use `database-query` for read-only DB queries
- Use `database-schema` to inspect table structure before writing migrations

## PHP Rules

- Always use curly braces for control structures, even single-line bodies
- Use PHP 8.4 constructor property promotion
- Always use explicit return type declarations and type hints
- Enum keys in TitleCase
- Prefer PHPDoc blocks over inline comments

## Architecture

- **Thin Controllers** — delegate to Actions, never put business logic in controllers
- **DTOs** in `app/DTOs/{Domain}/` — `final readonly` classes with constructor property promotion. Controllers build DTOs from validated request data and pass to Actions. DTOs must NOT depend on HTTP (no FormRequest imports)
- **Actions** in `app/Actions/{Domain}/` — one public `handle()` method. Receive DTOs (not FormRequests)
- **FormRequests** — always use form request classes, never inline validation. Include `messages()` method
- **Resources** — camelCase JSON output, use `whenLoaded()` for relationships
- **Policies** — use `Gate::authorize()` in controllers
- **Pagination** — always use `paginateFromRequest()` macro
- **Delete responses** — always use `MessageResource`
- **Routes** — versioned under `/api/v1` in `routes/api/v1.php`

## Database

- Use Eloquent models and relationships, avoid `DB::` facade
- Prefer `Model::query()` over `DB::`
- Eager load relationships to prevent N+1 queries
- `Model::shouldBeStrict()` is active in non-production environments
- PostGIS is available for spatial queries (ST_Distance, ST_Contains, etc.)
- When modifying columns, migrations must include all previously defined attributes

### Models

- Casts should use `casts()` method, not `$casts` property
- Use `php artisan make:model` with factories and seeders

## Authentication

- Use Laravel Sanctum with `auth:sanctum` middleware
- Token creation: `$user->createToken('api')->plainTextToken`
- Testing: use `Sanctum::actingAs($user, ['*'])`
- Never use `auth:api` (that's Passport)

## Testing

- PHPUnit only (never Pest — convert if found)
- Database: PostgreSQL (`empet_test_db`) — NOT SQLite (PostGIS functions require real PostgreSQL)
- Use `RefreshDatabase` trait
- Use `Sanctum::actingAs($user, ['*'])` for authentication in tests
- Use factories with states, never manually build complex models
- Test method names: `test_<description_in_snake_case>`
- Every endpoint must test: happy path, 401, 403, 422, edge cases
- Run minimal tests: `php artisan test --compact --filter=testName`
- Create tests with: `php artisan make:test --phpunit {Name}`

## Artisan Commands

- Always pass `--no-interaction` to artisan commands
- Use `php artisan make:` commands to scaffold files

## Code Formatting

- Run `vendor/bin/pint --dirty --format agent` after modifying PHP files
- Preset: `laravel` (PSR-12)

## Configuration

- Use `config()` helper, never `env()` directly outside config files
- Use queued jobs for time-consuming operations (`ShouldQueue`)

## Conventions

- Follow existing code conventions — check sibling files
- Use descriptive variable/method names
- Check for existing components before creating new ones
- Do not change dependencies or create new base folders without approval
- Only create documentation files if explicitly requested

## Docker

- Development uses Docker Compose with: postgres (PostGIS), redis, backend, backend-worker, backend-scheduler
- Network: `empet_net` (external)
- Run with: `docker compose up -d`
