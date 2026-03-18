<?php

namespace Tests\Feature\Notification;

use App\Channels\PushChannel;
use App\Contracts\PushNotificationService;
use App\DTOs\Notification\PushNotificationPayload;
use App\Events\InvalidPushDeviceDetected;
use App\Listeners\DeactivateInvalidDevices;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Tests\TestCase;

class PushChannelTest extends TestCase
{
    use RefreshDatabase;

    private function createTestNotification(): Notification
    {
        return new class extends Notification
        {
            public function toPush(object $notifiable): PushNotificationPayload
            {
                return new PushNotificationPayload(
                    title: 'Test Title',
                    body: 'Test Body',
                    data: ['type' => 'test'],
                );
            }

            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return [];
            }
        };
    }

    public function test_sends_push_to_active_devices(): void
    {
        $user = User::factory()->create();

        UserDevice::factory()->create([
            'user_id' => $user->id,
            'provider_device_id' => 'player-1',
            'is_active' => true,
        ]);
        UserDevice::factory()->create([
            'user_id' => $user->id,
            'provider_device_id' => 'player-2',
            'is_active' => true,
        ]);

        $mock = $this->mock(PushNotificationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendToDevices')
                ->once()
                ->withArgs(function (array $ids, PushNotificationPayload $payload) {
                    return count($ids) === 2
                        && $payload->title === 'Test Title'
                        && $payload->body === 'Test Body';
                })
                ->andReturn(true);
        });

        $channel = new PushChannel($mock);
        $channel->send($user, $this->createTestNotification());
    }

    public function test_does_not_send_when_no_devices(): void
    {
        $user = User::factory()->create();

        $mock = $this->mock(PushNotificationService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('sendToDevices');
        });

        $channel = new PushChannel($mock);
        $channel->send($user, $this->createTestNotification());
    }

    public function test_does_not_send_to_inactive_devices(): void
    {
        $user = User::factory()->create();

        UserDevice::factory()->inactive()->create([
            'user_id' => $user->id,
            'provider_device_id' => 'player-inactive',
        ]);

        $mock = $this->mock(PushNotificationService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('sendToDevices');
        });

        $channel = new PushChannel($mock);
        $channel->send($user, $this->createTestNotification());
    }

    public function test_does_not_send_to_devices_without_provider_id(): void
    {
        $user = User::factory()->create();

        UserDevice::factory()->withoutProvider()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $mock = $this->mock(PushNotificationService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('sendToDevices');
        });

        $channel = new PushChannel($mock);
        $channel->send($user, $this->createTestNotification());
    }

    public function test_provider_failure_does_not_throw(): void
    {
        $user = User::factory()->create();

        UserDevice::factory()->create([
            'user_id' => $user->id,
            'provider_device_id' => 'player-1',
            'is_active' => true,
        ]);

        $mock = $this->mock(PushNotificationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendToDevices')
                ->once()
                ->andThrow(new \RuntimeException('Provider down'));
        });

        Log::spy();

        $channel = new PushChannel($mock);
        $channel->send($user, $this->createTestNotification());

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($message) => str_contains($message, 'PushChannel'));
    }

    public function test_invalid_devices_are_deactivated_via_event(): void
    {
        $device = UserDevice::factory()->create([
            'provider_device_id' => 'invalid-player',
            'is_active' => true,
        ]);

        $event = new InvalidPushDeviceDetected(['invalid-player']);
        $listener = new DeactivateInvalidDevices;
        $listener->handle($event);

        $device->refresh();
        $this->assertFalse($device->is_active);
    }
}
