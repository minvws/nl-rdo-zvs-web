<?php

declare(strict_types=1);

namespace App\Enums;

enum PetitionEventChange: string
{
    case UNCHANGED = 'unchanged';
    case ADDED = 'added';
    case REMOVED = 'removed';
}
