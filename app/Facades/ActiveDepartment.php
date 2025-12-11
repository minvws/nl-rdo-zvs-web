<?php

declare(strict_types=1);

namespace App\Facades;

use App\Models\Department;
use App\Services\Authorisation\ActiveDepartmentService;
use Illuminate\Support\Facades\Facade;

/**
 * @see ActiveDepartmentService
 *
 * @method static bool hasActiveDepartment()
 * @method static Department|null getActiveDepartment()
 */
class ActiveDepartment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ActiveDepartmentService::class;
    }
}
