<?php

declare(strict_types=1);

namespace App\Enums;

enum SortDirection: string
{
    case ASC = 'ascending';
    case DESC = 'descending';
    case NONE = 'none';
}
