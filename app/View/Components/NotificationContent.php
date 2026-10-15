<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\Component;
use Illuminate\View\Factory;

class NotificationContent extends Component
{
    public function __construct(
        private readonly DatabaseNotification $notification,
        private readonly Factory $view,
    ) {
    }

    public function render(): View
    {
        /** @var string $notificationType */
        $notificationType = $this->notification->type;

        $viewName = 'components.notification.types.' . $notificationType;

        if (!$this->view->exists($viewName)) {
            return $this->renderFallback('View not found: ' . $viewName);
        }

        return $this->view->make($viewName, ['notification' => $this->notification]);
    }

    private function renderFallback(string $reason): View
    {
        return $this->view->make('components.notification.fallback', [
            'notification' => $this->notification,
            'reason' => $reason,
        ]);
    }
}
