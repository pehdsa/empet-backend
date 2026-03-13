---
name: webhook-development
description: "Develops webhook endpoints for external integrations. Activates when creating webhook controllers, routes, or actions; working with X-Webhook-Token, phone number resolution, or WebhookLog; or when user mentions webhook, chatbot, or external integration."
---

# Webhook Development

## When to Apply

Activate when:
- Creating or modifying webhook endpoints
- Working with external integrations
- Handling webhook authentication or logging
- Resolving users by phone number

## Project Webhook Architecture

Webhooks are external-facing endpoints consumed by external services/chatbots. They differ from regular API endpoints:
- **No Sanctum auth** — authenticated via `X-Webhook-Token` header
- **User resolved by phone number** — not from auth token
- **All requests logged** to `webhook_logs` table

### File Locations

| Layer | Path | Naming |
|-------|------|--------|
| Controllers | `app/Http/Controllers/Api/V1/Webhook/` | `Webhook{Model}Controller` |
| Actions | `app/Actions/Webhooks/` | Domain-specific actions |
| FormRequests | `app/Http/Requests/Webhook/` | `Webhook{Action}Request` |
| Middleware | `app/Http/Middleware/` | `ValidateWebhookToken`, `LogWebhookInteraction` |

### Middleware Stack

All webhook routes use these middleware (in order):
1. `webhook.log` — Logs request/response to `webhook_logs`
2. `webhook.token` — Validates `X-Webhook-Token` header
3. `throttle:30,1` — Rate limiting

```php
Route::prefix('webhooks')->middleware(['webhook.log', 'webhook.token', 'throttle:30,1'])->group(function (): void {
    Route::get('verify-user', WebhookVerifyUserController::class);
    Route::post('examples', [WebhookExampleController::class, 'store']);
});
```

### Token Validation

The `ValidateWebhookToken` middleware:
- Reads `X-Webhook-Token` from request header
- Compares with `config('services.webhook.token')` using `hash_equals()`
- Returns 401 `MessageResource` on failure

### User Resolution Pattern

Webhook endpoints identify users by phone number, not auth token:

```php
class WebhookExampleController extends Controller
{
    public function store(
        WebhookStoreExampleRequest $request,
        ResolveUserByPhoneNumberAction $resolveUser,
        CreateExampleAction $action,
    ): JsonResponse {
        $user = $resolveUser->handle($request->validated('phone_number'));

        $example = $action->handle($user, $request->safe()->except(['phone_number']));

        return (new ExampleResource($example))->response()->setStatusCode(201);
    }
}
```

**Key pattern**: Use `ResolveUserByPhoneNumberAction` to get the user, then delegate to existing domain Actions. This reuses business logic.

### Webhook FormRequests

Always include `phone_number` field:

```php
class WebhookStoreExampleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string'],
            // ... other fields
        ];
    }
}
```

### Testing Webhooks

```php
public function test_webhook_can_create_example(): void
{
    $user = User::factory()->create(['phone_number' => '+5511999999999']);

    $response = $this->postJson('/api/v1/webhooks/examples', [
        'phone_number' => '+5511999999999',
        // ... other fields
    ], [
        'X-Webhook-Token' => config('services.webhook.token'),
    ]);

    $response->assertCreated();
}

public function test_webhook_rejects_invalid_token(): void
{
    $response = $this->postJson('/api/v1/webhooks/examples', [], [
        'X-Webhook-Token' => 'wrong-token',
    ]);

    $response->assertUnauthorized();
}
```

## Rules

- Always include `phone_number` in webhook request validation
- Reuse existing domain Actions — don't duplicate business logic
- Use `ResolveUserByPhoneNumberAction` for user resolution
- Webhook config comes from `config('services.webhook.token')`, never `env()` directly
- Test both valid and invalid token scenarios
- Test webhook logging (`assertDatabaseHas('webhook_logs', ...)`)
