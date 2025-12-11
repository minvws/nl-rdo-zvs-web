<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RouteName;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

use function __;
use function now;
use function to_route;

final readonly class NotificationMarkAllAsReadController
{
    public function __invoke(#[CurrentUser] User $user): RedirectResponse
    {
        $user->unreadNotifications()->update(['read_at' => now()]);

        return to_route(RouteName::NOTIFICATIONS_INDEX)
            ->with('message.success', __('notification.marked_all_as_read'));
    }
}
