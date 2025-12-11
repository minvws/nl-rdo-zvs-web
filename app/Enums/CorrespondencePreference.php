<?php

declare(strict_types=1);

namespace App\Enums;

enum CorrespondencePreference: string
{
    case NONE = 'none';
    case EMAIL = 'email';
    case POST = 'post';
}
