<?php

declare(strict_types=1);

namespace App\Enums;

enum Ability: string
{
    case UPDATE = 'update';
    case UNARCHIVE = 'unarchive';
    case CREATE = 'create';
    case VIEW = 'view';
    case VIEW_ANY = 'viewAny';
    case DELETE = 'delete';
}
