<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\RouteName;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTestCase;

use function __;
use function now;
use function route;

class NotificationMarkAllAsReadControllerTest extends FeatureTestCase
{
    public function testMarksAllUnreadNotificationsAsRead(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user);

        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'App\\Notifications\\TestNotification',
            'data' => [
                'type' => 'petition-assigned',
                'title' => 'Ongelezen bericht 1',
            ],
            'read_at' => null,
        ]);

        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'App\\Notifications\\TestNotification',
            'data' => [
                'type' => 'petition-assigned',
                'title' => 'Ongelezen bericht 2',
            ],
            'read_at' => null,
        ]);

        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'App\\Notifications\\TestNotification',
            'data' => [
                'type' => 'petition-assigned',
                'title' => 'Gelezen bericht',
            ],
            'read_at' => now()->subDay(),
        ]);

        $response = $this->post(route(RouteName::NOTIFICATIONS_MARK_ALL_READ));

        $response->assertRedirect(route(RouteName::NOTIFICATIONS_INDEX));
        $response->assertSessionHas('message.success', __('notification.marked_all_as_read'));

        $this->assertSame(0, DatabaseNotification::query()->where('notifiable_id', $user->id)->whereNull('read_at')->count());
        $this->assertSame(3, DatabaseNotification::query()->where('notifiable_id', $user->id)->whereNotNull('read_at')->count());
    }

    public function testOnlyMarksNotificationsForAuthenticatedUser(): void
    {
        $user1 = User::factory()->fullyVerified()->create();
        $user2 = User::factory()->fullyVerified()->create();
        $this->beUser($user1);

        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user1->id,
            'notifiable_type' => 'user',
            'type' => 'App\\Notifications\\TestNotification',
            'data' => [
                'type' => 'petition-assigned',
                'title' => 'User 1 bericht',
            ],
            'read_at' => null,
        ]);

        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user2->id,
            'notifiable_type' => 'user',
            'type' => 'App\\Notifications\\TestNotification',
            'data' => [
                'type' => 'petition-assigned',
                'title' => 'User 2 bericht',
            ],
            'read_at' => null,
        ]);

        $this->post(route(RouteName::NOTIFICATIONS_MARK_ALL_READ));

        $this->assertSame(0, DatabaseNotification::query()->where('notifiable_id', $user1->id)->whereNull('read_at')->count());
        $this->assertSame(1, DatabaseNotification::query()->where('notifiable_id', $user2->id)->whereNull('read_at')->count());
    }
}
