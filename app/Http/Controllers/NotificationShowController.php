<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Ability;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\View as ViewFacade;

use function abort;

final readonly class NotificationShowController
{
    public function __invoke(DatabaseNotification $notification, Request $request, #[CurrentUser] User $user): View
    {
        if ($user->cannot(Ability::VIEW, $notification)) {
            abort(403);
        }

        $notification->markAsRead(); // if we show the detail, we mark it as read

        return ViewFacade::make('notification.show', [
            'notification' => $notification,
        ]);
    }
}
