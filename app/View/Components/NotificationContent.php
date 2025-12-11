<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Component;
use Illuminate\View\Factory;
use Throwable;

class NotificationContent extends Component
{
    public function __construct(
        private readonly DatabaseNotification $notification,
        private readonly Factory $view,
    ) {
    }

    public function render(): View
    {
        try {
            /** @var string $notificationType */
            $notificationType = $this->notification->type;

            return $this->view->make(
                'components.notification.types.' . $notificationType,
                ['notification' => $this->notification],
            );
        } catch (Throwable $e) {
            Log::warning('Failed to render notification content', [
                'notification_id' => $this->notification->id,
                'notification_type' => $this->notification->type,
                'error' => $e->getMessage(),
            ]);

            return $this->renderFallback($e->getMessage());
        }
    }

    private function renderFallback(string $reason): View
    {
        return $this->view->make('components.notification.fallback', [
            'notification' => $this->notification,
            'reason' => $reason,
        ]);
    }
}
