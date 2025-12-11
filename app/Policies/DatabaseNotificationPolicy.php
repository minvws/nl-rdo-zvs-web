<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class DatabaseNotificationPolicy
{
    public function view(User $user, DatabaseNotification $databaseNotification): bool
    {
        /** @var string $notifiableId */
        $notifiableId = $databaseNotification->notifiable_id;

        return $user->id->toString() === $notifiableId;
    }

    public function update(User $user, DatabaseNotification $databaseNotification): bool
    {
        /** @var string $notifiableId */
        $notifiableId = $databaseNotification->notifiable_id;

        return $user->id->toString() === $notifiableId;
    }
}
