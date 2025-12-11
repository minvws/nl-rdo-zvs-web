<?php

declare(strict_types=1);

namespace App\Enums;

enum DecisionCriteria: string
{
    case ARCHIVE = 'archive';
    case NAME = 'name';
    case DATE = 'date';
    case REFERENCE = 'reference';
    case DEADLINE = 'deadline';
    case PROGRESS = 'progress';
    case SEARCH = 'search';
    case TYPE = 'type';
}
