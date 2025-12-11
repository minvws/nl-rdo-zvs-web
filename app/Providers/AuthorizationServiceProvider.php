<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Authorization\Permission;
use App\Models\User;
use App\Services\Authorisation\ActiveDepartmentService;
use App\Services\Authorisation\UserPermissionService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthorizationServiceProvider extends ServiceProvider
{
    public function boot(
        UserPermissionService $userPermissionService,
        ActiveDepartmentService $activeDepartmentService,
    ): void {
        foreach (Permission::cases() as $permission) {
            Gate::define(
                $permission,
                static function (User $user) use ($permission, $userPermissionService, $activeDepartmentService): bool {
                    $activeDepartment = $activeDepartmentService->getActiveDepartment();

                    return $userPermissionService->hasPermission($permission, $user, $activeDepartment);
                },
            );
        }
    }
}
