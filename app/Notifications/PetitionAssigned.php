<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\RouteName;
use App\Models\Petition;
use Illuminate\Notifications\Notification;

use function __;
use function route;

class PetitionAssigned extends Notification
{
    public function __construct(private readonly Petition $petition)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notification.petition_assigned :number', ['number' => $this->petition->number]),
            'petition_id' => $this->petition->id,
            'description' => $this->petition->description,
            'url' => route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $this->petition->department,
                'petition' => $this->petition->id,
            ], false),
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'petition-assigned';
    }
}
