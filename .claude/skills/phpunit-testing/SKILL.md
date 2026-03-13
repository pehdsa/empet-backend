---
name: phpunit-testing
description: "Writes and runs PHPUnit tests following project conventions. Activates when creating tests, writing assertions, using factories, debugging test failures, or when user mentions test, TDD, PHPUnit, coverage, or assertions."
---

# PHPUnit Testing

## When to Apply

Activate when:
- Creating or modifying tests
- Debugging test failures
- Setting up factories or test data
- Doing TDD (test-driven development)

## Documentation

Use `search-docs` with `packages: ["phpunit/phpunit"]` for PHPUnit v11 specifics.

## Project Test Setup

- **Framework**: PHPUnit v11 (never Pest — convert if found)
- **Location**: `tests/Feature/` for feature tests, `tests/Unit/` for unit tests
- **Database**: PostgreSQL (`empet_test_db`) — NOT SQLite (PostGIS functions require real PostgreSQL)
- **Auth**: `Sanctum::actingAs($user, ['*'])` (never simulate token flow)
- **Create tests**: `php artisan make:test --phpunit {Name}` (feature) or `--unit` (unit)

## Test Structure

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_example(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/examples', [
            'name' => 'Test Example',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'createdAt'],
            ]);

        $this->assertDatabaseHas('examples', [
            'user_id' => $user->id,
            'name' => 'Test Example',
        ]);
    }
}
```

## Required Test Coverage

Every endpoint must test:

### Happy Path
- Successful operation with valid data
- Correct response structure and status code
- Database state after operation

### Authentication (401)
```php
public function test_unauthenticated_user_cannot_create_example(): void
{
    $response = $this->postJson('/api/v1/examples', []);

    $response->assertUnauthorized();
}
```

### Authorization (403)
```php
public function test_user_cannot_update_another_users_example(): void
{
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $example = Example::factory()->for($owner)->create();
    Sanctum::actingAs($otherUser, ['*']);

    $response = $this->putJson("/api/v1/examples/{$example->id}", [
        'name' => 'Updated',
    ]);

    $response->assertForbidden();
}
```

### Validation (422)
```php
public function test_example_requires_name(): void
{
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/examples', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
}
```

### Edge Cases
- Soft-deleted related records
- Boundary values (min/max)
- Duplicate operations
- Owner isolation (user can't access other user's data)

## Factories

Always use factories to create test data. Check available states before manually setting attributes:

```php
// Use factory states
$admin = User::factory()->admin()->create();

// Use `for()` for relationships
$example = Example::factory()->for($user)->create();

// Override specific attributes
$example = Example::factory()->create(['name' => 'Custom']);
```

## Faker

Follow existing convention — use `fake()` helper:
```php
fake()->word()
fake()->randomDigit()
fake()->sentence()
```

## Running Tests

```bash
# Single test method
php artisan test --compact --filter=test_authenticated_user_can_create_example

# All tests in a file
php artisan test --compact tests/Feature/CreateExampleTest.php

# Full suite
php artisan test --compact
```

## Rules

- Always use `RefreshDatabase` trait
- Always use `Sanctum::actingAs($user, ['*'])` for auth
- Use factories with states, never manually build complex models
- Test method names: `test_<description_in_snake_case>`
- One assertion group per test (focused tests)
- Run `php artisan test --compact --filter=testName` after writing/modifying a test
- After feature tests pass, ask user if they want to run the full suite
