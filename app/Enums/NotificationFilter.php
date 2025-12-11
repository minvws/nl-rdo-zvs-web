<?php

declare(strict_types=1);

namespace App\Enums;

use function __;

enum NotificationFilter: string
{
    case ALL = 'all';
    case UNREAD = 'unread';
    case READ = 'read';

    public function label(): string
    {
        return match ($this) {
            self::ALL => __('notification.all'),
            self::UNREAD => __('notification.unread'),
            self::READ => __('notification.read'),
        };
    }
}
