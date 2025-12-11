<?php

declare(strict_types=1);

namespace App\Enums;

enum QuerysnapshotType: string
{
    case CHAT = 'chat';
    case DOCUMENT = 'document';
}
