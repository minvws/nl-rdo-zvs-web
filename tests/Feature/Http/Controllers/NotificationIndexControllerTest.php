<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\RouteName;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTestCase;

use function now;

class NotificationIndexControllerTest extends FeatureTestCase
{
    public function testIndexShowsNotifications(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user);

        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'test-notification',
            'data' => [
                'title' => 'Ongelezen bericht',
            ],
            'read_at' => null,
        ]);
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'test-notification',
            'data' => [
                'title' => 'Gelezen bericht',
            ],
            'read_at' => now(),
        ]);

        $response = $this->getByRoute(RouteName::NOTIFICATIONS_INDEX);
        $response->assertOk();
        $response->assertSee('Ongelezen bericht');
        $response->assertSee('Gelezen bericht');
    }

    public function testIndexFiltersUnread(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user);

        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'test-notification',
            'data' => [
                'title' => 'Ongelezen bericht',
            ],
            'read_at' => null,
        ]);
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'test-notification',
            'data' => [
                'title' => 'Gelezen bericht',
            ],
            'read_at' => now(),
        ]);

        $response = $this->getByRoute(RouteName::NOTIFICATIONS_INDEX, ['filter' => 'unread']);
        $response->assertOk();
        $response->assertSee('Ongelezen bericht');
        $response->assertDontSee('Gelezen bericht');
    }

    public function testIndexFiltersRead(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user);

        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'test-notification',
            'data' => [
                'title' => 'Ongelezen bericht',
            ],
            'read_at' => null,
        ]);
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'test-notification',
            'data' => [
                'title' => 'Gelezen bericht',
            ],
            'read_at' => now(),
        ]);

        $response = $this->getByRoute(RouteName::NOTIFICATIONS_INDEX, ['filter' => 'read']);
        $response->assertOk();
        $response->assertSee('Gelezen bericht');
        $response->assertDontSee('Ongelezen bericht');
    }
}
