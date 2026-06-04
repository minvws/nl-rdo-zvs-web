<?php

declare(strict_types=1);

namespace App\Enums;

enum AssignmentRole: int
{
    case PRIMARY = 1;
    case SECONDARY = 2;
}
