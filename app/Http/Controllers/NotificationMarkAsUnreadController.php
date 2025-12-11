<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Ability;
use App\Enums\RouteName;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

use function __;
use function abort;
use function to_route;

final readonly class NotificationMarkAsUnreadController
{
    public function __invoke(DatabaseNotification $notification, #[CurrentUser] User $user): RedirectResponse
    {
        if ($user->cannot(Ability::UPDATE, $notification)) {
            abort(403);
        }

        $notification->markAsUnread();

        return to_route(RouteName::NOTIFICATIONS_INDEX)
            ->with('message.success', __('notification.marked_as_unread'));
    }
}
