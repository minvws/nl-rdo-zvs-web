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

class NotificationShowControllerTest extends FeatureTestCase
{
    public function testShowsNotificationDetail(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user);

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['title' => 'Test Notificatie'],
            'read_at' => null,
        ]);

        $response = $this->getByRoute(RouteName::NOTIFICATIONS_SHOW, ['notification' => $notification->id]);
        $response->assertOk();
        $response->assertSee('Test Notificatie');
        $response->assertSee('App\Notifications\TestNotification');
    }

    public function testShowsPetitionAssignedNotification(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user);

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'App\\Notifications\\PetitionAssigned',
            'data' => [
                'title' => 'Zaak Toegewezen',
                'description' => 'Vergunning 2024-001',
                'url' => '/petitions/123',
                'assigned_by' => 'Jan Jansen',
            ],
            'read_at' => null,
        ]);

        $response = $this->getByRoute(RouteName::NOTIFICATIONS_SHOW, ['notification' => $notification->id]);
        $response->assertOk();
        $response->assertSee('Zaak Toegewezen');
        // Since component for App\Notifications\PetitionAssigned doesn't exist, fallback is shown
        $response->assertSee('App\Notifications\PetitionAssigned');
    }

    public function testRequiresAuthentication(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['title' => 'Test'],
            'read_at' => null,
        ]);

        $response = $this->get(route(RouteName::NOTIFICATIONS_SHOW->value, ['notification' => $notification->id]));
        $response->assertRedirect(route(RouteName::LOGIN->value));
    }

    public function testCannotViewOtherUsersNotifications(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user);

        $otherUser = User::factory()->fullyVerified()->create();
        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $otherUser->id,
            'notifiable_type' => 'user',
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['title' => 'Anderen zijn notificatie'],
            'read_at' => null,
        ]);

        $response = $this->getByRoute(RouteName::NOTIFICATIONS_SHOW, ['notification' => $notification->id]);
        $response->assertForbidden();
    }

    public function testShowsMarkAsUnreadButton(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user);

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'petition-assigned',
            'data' => ['description' => 'Test', 'petition_id' => 1, 'title' => 'Zaak Toegewezen', 'url' => '/petitions/1'],
            'read_at' => null,
        ]);

        $response = $this->getByRoute(RouteName::NOTIFICATIONS_SHOW, ['notification' => $notification->id]);
        $response->assertOk();
        $response->assertSee(__('notification.back_to_notifications_and_mark_as_unread'));
    }

    public function testShowsFallbackForUnknownNotificationType(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user);

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'notifiable_id' => $user->id,
            'notifiable_type' => 'user',
            'type' => 'App\\Notifications\\UnknownNotification',
            'data' => [
                'title' => 'Onbekend Type',
            ],
            'read_at' => null,
        ]);

        $response = $this->getByRoute(RouteName::NOTIFICATIONS_SHOW, ['notification' => $notification->id]);
        $response->assertOk();
        $response->assertSee('Onbekend Type');
        $response->assertSee('App\Notifications\UnknownNotification');
    }
}
