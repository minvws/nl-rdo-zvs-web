<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\RouteName;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTestCase;

use function __;
use function route;

class NotificationMarkAsReadControllerTest extends FeatureTestCase
{
    public function testMarksNotificationAsRead(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user);

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['title' => 'Test bericht'],
            'read_at' => null,
        ]);

        $response = $this->post(route(RouteName::NOTIFICATIONS_MARK_AS_READ, $notification->id));

        $response->assertRedirect(route(RouteName::NOTIFICATIONS_INDEX));
        $response->assertSessionHas('message.success', __('notification.marked_as_read'));

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function testRequiresAuthentication(): void
    {
        $user = User::factory()->fullyVerified()->create();

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['title' => 'Test bericht'],
            'read_at' => null,
        ]);

        $response = $this->post(route(RouteName::NOTIFICATIONS_MARK_AS_READ, $notification->id));

        $response->assertRedirect(route(RouteName::LOGIN));
    }

    public function testCannotMarkOtherUsersNotifications(): void
    {
        $user1 = User::factory()->fullyVerified()->create();
        $user2 = User::factory()->fullyVerified()->create();
        $this->beUser($user1);

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user2->id,
            'notifiable_type' => 'user',
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['title' => 'Test bericht'],
            'read_at' => null,
        ]);

        $response = $this->post(route(RouteName::NOTIFICATIONS_MARK_AS_READ, $notification->id));

        $response->assertForbidden();
    }
}
