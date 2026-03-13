---
name: sanctum-auth
description: "Handles authentication with Laravel Sanctum. Activates when working with login, logout, tokens, auth middleware, Sanctum configuration, or protected routes; or when user mentions authentication, authorization, auth:sanctum, or access tokens."
---

# Sanctum Authentication

## When to Apply

Activate when:
- Creating or modifying authenticated routes
- Working with login/logout/token flows
- Configuring Sanctum
- Writing tests that require authentication
- Adding middleware `auth:sanctum` to routes

## Documentation

Use `search-docs` with `packages: ["laravel/framework"]` and queries like `["sanctum", "api token authentication"]` for version-specific docs.

## Project Setup

- **Laravel Sanctum** with API token authentication
- Middleware: `auth:sanctum`
- Token creation: `$user->createToken('api')->plainTextToken`
- Stateless API (no cookies, no sessions for API)

## Middleware Groups

```php
// Authenticated only
Route::middleware('auth:sanctum')->group(function (): void { ... });
```

## User Model

- Must use `HasApiTokens` trait
- Has `role` (enum `UserRole`) and `status` (enum `UserStatus`) when applicable

## Token Management

### Creating Tokens (Login)
```php
$token = $user->createToken('api')->plainTextToken;

return response()->json([
    'token' => $token,
    'user' => new UserResource($user),
]);
```

### Revoking Tokens (Logout)
```php
$request->user()->currentAccessToken()->delete();
```

### Revoking All Tokens
```php
$request->user()->tokens()->delete();
```

## Testing Authentication

**CRITICAL**: Always use `Sanctum::actingAs()` for test authentication:

```php
use Laravel\Sanctum\Sanctum;

public function test_authenticated_user_can_access(): void
{
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/auth/me');

    $response->assertOk();
}
```

### Testing Unauthenticated Access (401)

```php
public function test_unauthenticated_user_cannot_access(): void
{
    $response = $this->getJson('/api/v1/examples');

    $response->assertUnauthorized();
}
```

### Testing Unauthorized Access (403)

```php
public function test_non_admin_cannot_list_users(): void
{
    $user = User::factory()->create(); // regular user
    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/users');

    $response->assertForbidden();
}
```

## Rules

- Always use `auth:sanctum` middleware, NOT `auth:api` (that's Passport)
- Use `Gate::authorize()` for policy checks in controllers
- Use `Sanctum::actingAs($user, ['*'])` in tests — NOT `$this->actingAs()`
- New authenticated routes should include `auth:sanctum` middleware
- Never expose token in logs or responses after creation
