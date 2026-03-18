<?php

namespace Tests\Feature\Notification;

use App\Jobs\NotifyNearbyUsersOfLostPet;
use App\Models\Pet;
use App\Models\PetReport;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Notifications\PetLostNearby;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotifyNearbyUsersTest extends TestCase
{
    use RefreshDatabase;

    private function createLostReport(User $owner, float $lat = -23.5505, float $lng = -46.6333): PetReport
    {
        $pet = Pet::factory()->create(['user_id' => $owner->id]);
        $report = PetReport::factory()->lost()->create([
            'user_id' => $owner->id,
            'pet_id' => $pet->id,
        ]);

        DB::statement(
            'UPDATE pet_reports SET location = ST_MakePoint(?, ?)::geography WHERE id = ?',
            [$lng, $lat, $report->id]
        );

        return $report;
    }

    private function createUserWithLocation(float $lat, float $lng, array $settingOverrides = []): User
    {
        $user = User::factory()->create();
        $setting = UserNotificationSetting::factory()->create(array_merge(
            ['user_id' => $user->id],
            $settingOverrides,
        ));

        DB::statement(
            'UPDATE user_notification_settings SET location = ST_MakePoint(?, ?)::geography WHERE id = ?',
            [$lng, $lat, $setting->id]
        );

        return $user;
    }

    public function test_notifies_users_within_radius(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReport($owner, -23.5505, -46.6333);

        // ~700m from report — within default 5km
        $nearbyUser = $this->createUserWithLocation(-23.5550, -46.6380);

        $job = new NotifyNearbyUsersOfLostPet($report);
        $job->handle();

        Notification::assertSentTo($nearbyUser, PetLostNearby::class);
    }

    public function test_does_not_notify_pet_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReport($owner, -23.5505, -46.6333);

        // Owner is at same location
        $setting = UserNotificationSetting::factory()->create(['user_id' => $owner->id]);
        DB::statement(
            'UPDATE user_notification_settings SET location = ST_MakePoint(?, ?)::geography WHERE id = ?',
            [-46.6333, -23.5505, $setting->id]
        );

        $job = new NotifyNearbyUsersOfLostPet($report);
        $job->handle();

        Notification::assertNotSentTo($owner, PetLostNearby::class);
    }

    public function test_does_not_notify_users_outside_radius(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReport($owner, -23.5505, -46.6333);

        // ~20km away — outside default 5km
        $farUser = $this->createUserWithLocation(-23.7000, -46.8000);

        $job = new NotifyNearbyUsersOfLostPet($report);
        $job->handle();

        Notification::assertNotSentTo($farUser, PetLostNearby::class);
    }

    public function test_does_not_notify_users_with_disabled_preference(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReport($owner, -23.5505, -46.6333);

        $disabledUser = $this->createUserWithLocation(-23.5550, -46.6380, [
            'notify_lost_nearby' => false,
        ]);

        $job = new NotifyNearbyUsersOfLostPet($report);
        $job->handle();

        Notification::assertNotSentTo($disabledUser, PetLostNearby::class);
    }

    public function test_does_not_notify_users_without_location(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReport($owner, -23.5505, -46.6333);

        $user = User::factory()->create();
        UserNotificationSetting::factory()->create([
            'user_id' => $user->id,
            'notify_lost_nearby' => true,
        ]);
        // No location set

        $job = new NotifyNearbyUsersOfLostPet($report);
        $job->handle();

        Notification::assertNotSentTo($user, PetLostNearby::class);
    }

    public function test_individual_radius_is_respected(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $report = $this->createLostReport($owner, -23.5505, -46.6333);

        // ~8km from report, with radius of 10km — should be notified
        $largeRadiusUser = $this->createUserWithLocation(-23.6200, -46.6800, [
            'nearby_radius_km' => 10,
        ]);

        // ~8km from report, with radius of 3km — should NOT be notified
        $smallRadiusUser = $this->createUserWithLocation(-23.6200, -46.6800, [
            'nearby_radius_km' => 3,
        ]);

        $job = new NotifyNearbyUsersOfLostPet($report);
        $job->handle();

        Notification::assertSentTo($largeRadiusUser, PetLostNearby::class);
        Notification::assertNotSentTo($smallRadiusUser, PetLostNearby::class);
    }

    public function test_idempotency_no_duplicate_notifications(): void
    {
        $owner = User::factory()->create();
        $report = $this->createLostReport($owner, -23.5505, -46.6333);

        $nearbyUser = $this->createUserWithLocation(-23.5550, -46.6380);

        // Run job twice without Notification::fake — actual DB writes
        $job = new NotifyNearbyUsersOfLostPet($report);
        $job->handle();
        $job->handle();

        $notificationCount = $nearbyUser->notifications()
            ->where('type', PetLostNearby::class)
            ->count();

        $this->assertEquals(1, $notificationCount);
    }
}
