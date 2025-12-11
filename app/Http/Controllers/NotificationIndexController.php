<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationFilter;
use App\Models\User;
use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;

final readonly class NotificationIndexController
{
    public function __construct(
        #[Config('app.pagination.items_per_page')]
        private int $itemsPerPage,
    ) {
    }

    public function __invoke(Request $request, #[CurrentUser] User $user): View
    {
        $filter = $request->query('filter', NotificationFilter::ALL->value);

        $notifications = match ($filter) {
            NotificationFilter::UNREAD->value => $user->unreadNotifications(),
            NotificationFilter::READ->value => $user->readNotifications(),
            default => $user->notifications(),
        };

        $unreadCount = $user->unreadNotifications()->count();

        return ViewFacade::make('notification.index', [
            'notifications' => $notifications->paginate($this->itemsPerPage),
            'filter' => $filter,
            'unreadCount' => $unreadCount,
            'notificationFilters' => NotificationFilter::cases(),
        ]);
    }
}
