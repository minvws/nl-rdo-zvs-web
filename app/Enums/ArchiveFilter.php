<?php

declare(strict_types=1);

namespace App\Enums;

use function __;

enum ArchiveFilter: string
{
    case HIDE_ARCHIVED = 'hide_archived';
    case SHOW_ARCHIVED = 'show_archived';
    case SHOW_ALL = 'show_all';

    public function label(): string
    {
        return match ($this) {
            self::HIDE_ARCHIVED => __('petition.filter.archive.hide_archived'),
            self::SHOW_ARCHIVED => __('petition.filter.archive.show_archived'),
            self::SHOW_ALL => __('petition.filter.archive.show_all'),
        };
    }
}
