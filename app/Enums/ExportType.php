<?php

declare(strict_types=1);

namespace App\Enums;

enum ExportType: string
{
    case INTERNAL = 'internal';
    case DASHBOARD = 'dashboard';
}
