<?php

namespace Tests\Feature\UserPhone;

use App\Models\User;
use App\Models\UserPhone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserPhoneCrudTest extends TestCase
{
    use RefreshDatabase;

    // ─── INDEX ───────────────────────────────────────────────

    public function test_index_returns_user_phones_ordered_primary_first(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $phoneA = UserPhone::factory()->create(['user_id' => $user->id, 'is_primary' => false]);
        $phoneB = UserPhone::factory()->primary()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/v1/user/phones');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $phoneB->id)
            ->assertJsonPath('data.1.id', $phoneA->id);
    }

    public function test_index_returns_empty_when_no_phones(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/user/phones')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/user/phones')
            ->assertUnauthorized();
    }

    // ─── STORE ───────────────────────────────────────────────

    public function test_store_creates_phone(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/user/phones', [
            'phone' => '+5511999999999',
            'is_whatsapp' => true,
            'is_primary' => false,
            'label' => 'Celular',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.phone', '+5511999999999')
            ->assertJsonPath('data.isWhatsapp', true)
            ->assertJsonPath('data.label', 'Celular');

        $this->assertDatabaseHas('user_phones', [
            'user_id' => $user->id,
            'phone' => '+5511999999999',
        ]);
    }

    public function test_store_first_phone_becomes_primary_automatically(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/user/phones', [
            'phone' => '+5511999999999',
            'is_primary' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.isPrimary', true);
    }

    public function test_store_marking_primary_unmarks_others(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $existing = UserPhone::factory()->primary()->create(['user_id' => $user->id]);

        $response = $this->postJson('/api/v1/user/phones', [
            'phone' => '+5521988888888',
            'is_primary' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.isPrimary', true);

        $this->assertFalse($existing->refresh()->is_primary);
    }

    public function test_store_fails_when_limit_of_5_reached(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        UserPhone::factory()->count(5)->create(['user_id' => $user->id]);

        $this->postJson('/api/v1/user/phones', [
            'phone' => '+5511000000000',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_store_fails_with_duplicate_phone_for_same_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        UserPhone::factory()->create(['user_id' => $user->id, 'phone' => '+5511999999999']);

        $this->postJson('/api/v1/user/phones', [
            'phone' => '+5511999999999',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_store_allows_same_phone_for_different_users(): void
    {
        $otherUser = User::factory()->create();
        UserPhone::factory()->create(['user_id' => $otherUser->id, 'phone' => '+5511999999999']);

        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/user/phones', [
            'phone' => '+5511999999999',
        ])->assertCreated();
    }

    public function test_store_trims_phone_and_converts_empty_label_to_null(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/user/phones', [
            'phone' => '  +5511999999999  ',
            'label' => '',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.phone', '+5511999999999')
            ->assertJsonPath('data.label', null);
    }

    public function test_store_fails_with_empty_phone_after_trim(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/user/phones', [
            'phone' => '   ',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/user/phones', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/user/phones', [
            'phone' => '+5511999999999',
        ])->assertUnauthorized();
    }

    // ─── UPDATE ──────────────────────────────────────────────

    public function test_update_modifies_phone(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $phone = UserPhone::factory()->primary()->create(['user_id' => $user->id]);

        $response = $this->putJson("/api/v1/user/phones/{$phone->id}", [
            'phone' => '+5521988888888',
            'is_whatsapp' => true,
            'is_primary' => true,
            'label' => 'Trabalho',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.phone', '+5521988888888')
            ->assertJsonPath('data.isWhatsapp', true)
            ->assertJsonPath('data.label', 'Trabalho');
    }

    public function test_update_marking_primary_unmarks_others(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $primary = UserPhone::factory()->primary()->create(['user_id' => $user->id]);
        $secondary = UserPhone::factory()->create(['user_id' => $user->id, 'is_primary' => false]);

        $this->putJson("/api/v1/user/phones/{$secondary->id}", [
            'phone' => $secondary->phone,
            'is_primary' => true,
        ])->assertOk();

        $this->assertFalse($primary->refresh()->is_primary);
        $this->assertTrue($secondary->refresh()->is_primary);
    }

    public function test_update_removing_primary_promotes_another(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $primary = UserPhone::factory()->primary()->create(['user_id' => $user->id]);
        $other = UserPhone::factory()->create(['user_id' => $user->id, 'is_primary' => false]);

        $this->putJson("/api/v1/user/phones/{$primary->id}", [
            'phone' => $primary->phone,
            'is_primary' => false,
        ])->assertOk();

        $this->assertFalse($primary->refresh()->is_primary);
        $this->assertTrue($other->refresh()->is_primary);
    }

    public function test_update_fails_with_duplicate_phone_for_same_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        UserPhone::factory()->create(['user_id' => $user->id, 'phone' => '+5511999999999']);
        $phone = UserPhone::factory()->create(['user_id' => $user->id, 'phone' => '+5521988888888']);

        $this->putJson("/api/v1/user/phones/{$phone->id}", [
            'phone' => '+5511999999999',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_update_allows_keeping_same_phone_number(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $phone = UserPhone::factory()->primary()->create(['user_id' => $user->id, 'phone' => '+5511999999999']);

        $this->putJson("/api/v1/user/phones/{$phone->id}", [
            'phone' => '+5511999999999',
            'label' => 'Novo label',
        ])->assertOk();
    }

    public function test_update_trims_and_converts_empty_label_to_null(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $phone = UserPhone::factory()->primary()->create(['user_id' => $user->id, 'label' => 'Celular']);

        $this->putJson("/api/v1/user/phones/{$phone->id}", [
            'phone' => $phone->phone,
            'label' => '  ',
        ])->assertOk()
            ->assertJsonPath('data.label', null);
    }

    public function test_update_returns_404_for_other_users_phone(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $otherPhone = UserPhone::factory()->create();

        $this->putJson("/api/v1/user/phones/{$otherPhone->id}", [
            'phone' => '+5511000000000',
        ])->assertNotFound();
    }

    public function test_update_requires_authentication(): void
    {
        $phone = UserPhone::factory()->create();

        $this->putJson("/api/v1/user/phones/{$phone->id}", [
            'phone' => '+5511000000000',
        ])->assertUnauthorized();
    }

    // ─── DESTROY ─────────────────────────────────────────────

    public function test_destroy_deletes_phone(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $phone = UserPhone::factory()->primary()->create(['user_id' => $user->id]);

        $this->deleteJson("/api/v1/user/phones/{$phone->id}")
            ->assertOk()
            ->assertJsonPath('data.message', 'Phone deleted successfully.');

        $this->assertDatabaseMissing('user_phones', ['id' => $phone->id]);
    }

    public function test_destroy_primary_promotes_another(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $primary = UserPhone::factory()->primary()->create(['user_id' => $user->id]);
        $other = UserPhone::factory()->create(['user_id' => $user->id, 'is_primary' => false]);

        $this->deleteJson("/api/v1/user/phones/{$primary->id}")
            ->assertOk();

        $this->assertTrue($other->refresh()->is_primary);
    }

    public function test_destroy_only_phone_leaves_empty_list(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $phone = UserPhone::factory()->primary()->create(['user_id' => $user->id]);

        $this->deleteJson("/api/v1/user/phones/{$phone->id}")
            ->assertOk();

        $this->assertSame(0, $user->phones()->count());
    }

    public function test_destroy_returns_404_for_other_users_phone(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $otherPhone = UserPhone::factory()->create();

        $this->deleteJson("/api/v1/user/phones/{$otherPhone->id}")
            ->assertNotFound();
    }

    public function test_destroy_requires_authentication(): void
    {
        $phone = UserPhone::factory()->create();

        $this->deleteJson("/api/v1/user/phones/{$phone->id}")
            ->assertUnauthorized();
    }
}
