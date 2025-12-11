<?php

declare(strict_types=1);

namespace App\Enums\Authorization;

enum DepartmentRole: string
{
    case WRITE = 'write';
    case READ = 'read';
}
