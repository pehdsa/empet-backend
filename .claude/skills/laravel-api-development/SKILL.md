---
name: laravel-api-development
description: "Develops Laravel API endpoints following project conventions. Activates when creating controllers, actions, form requests, resources, policies, or API routes; when user mentions API, endpoint, CRUD, action, resource, or form request."
---

# Laravel API Development

## When to Apply

Activate when:
- Creating or modifying controllers, actions, form requests, resources, or policies
- Adding new API endpoints or routes
- Working with Eloquent models, relationships, or query building
- Implementing CRUD operations

## Documentation

Use `search-docs` for version-specific Laravel 12 patterns before writing code.

## Project Architecture

This is a Laravel 12 **API-only** application. All API routes are versioned under `/api/v1`.

### File Locations

| Layer | Path | Naming |
|-------|------|--------|
| Controllers | `app/Http/Controllers/Api/V1/` | `{Model}Controller` |
| Actions | `app/Actions/{Domain}/` | `{Verb}{Model}Action` (e.g., `CreateExpenseAction`) |
| FormRequests | `app/Http/Requests/` | `Store{Model}Request`, `Update{Model}Request` |
| Resources | `app/Http/Resources/` | `{Model}Resource` |
| Policies | `app/Policies/` | `{Model}Policy` |
| Routes | `routes/api/v1.php` | Grouped by middleware |

### Controller Pattern — Thin Controllers

Controllers only delegate to Actions. Never put business logic in controllers.

```php
class ExampleController extends Controller
{
    public function index(Request $request, ListExamplesAction $action): AnonymousResourceCollection
    {
        return ExampleResource::collection($action->handle($request->user()));
    }

    public function store(StoreExampleRequest $request, CreateExampleAction $action): JsonResponse
    {
        $example = $action->handle($request->user(), $request->validated());

        return (new ExampleResource($example))->response()->setStatusCode(201);
    }

    public function show(Example $example): ExampleResource
    {
        Gate::authorize('view', $example);

        return new ExampleResource($example->load(['relation']));
    }

    public function update(UpdateExampleRequest $request, Example $example, UpdateExampleAction $action): ExampleResource
    {
        Gate::authorize('update', $example);

        return new ExampleResource($action->handle($example, $request->validated()));
    }

    public function destroy(Example $example, DeleteExampleAction $action): MessageResource
    {
        Gate::authorize('delete', $example);

        $action->handle($example);

        return new MessageResource('Example deleted successfully.');
    }
}
```

### Action Pattern

- One public `handle()` method per action
- Accept `User` and/or validated data array
- Return model with relationships loaded

```php
class CreateExampleAction
{
    public function handle(User $user, array $data): Example
    {
        $example = $user->examples()->create($data);

        return $example->load(['relation']);
    }
}
```

### FormRequest Pattern

- Array-based validation rules
- Always include `messages()` method
- `authorize()` returns `true` (authorization handled in controller via Policy)

```php
class StoreExampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
        ];
    }
}
```

### Resource Pattern

- Use `whenLoaded()` for conditional relationships
- ISO string format for dates

```php
class ExampleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'relation' => new RelationResource($this->whenLoaded('relation')),
            'createdAt' => $this->created_at->toISOString(),
        ];
    }
}
```

### Delete Responses

Always use `MessageResource` for delete endpoints:
```php
return new MessageResource('Resource deleted successfully.');
```

### Policy Pattern

- Owner-based authorization: `$user->id === $model->user_id`
- Use `Gate::authorize()` in controllers

### Pagination

Always use `paginateFromRequest()` macro for list endpoints:
```php
$query->paginateFromRequest(orderBy: 'name', initialDirection: 'asc');
```
Supports: `?page=1`, `?per_page=15`, `?sort=column:asc`

### Route Registration

Routes in `routes/api/v1.php`, grouped by middleware:
```php
// Authenticated routes
Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('examples', ExampleController::class)->names('api.v1.examples');
});
```

## Rules

- Use `php artisan make:` commands to scaffold files
- Pass `--no-interaction` to all artisan commands
- Avoid `DB::` — use `Model::query()`
- Eager load relationships to prevent N+1
- Run `vendor/bin/pint --dirty --format agent` after PHP changes
- Write PHPUnit tests for all endpoints (happy path, 401, 403, 422, edge cases)
