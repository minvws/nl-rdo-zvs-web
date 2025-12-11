<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Authorization\Permission;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\Services\Authorisation\UserPermissionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Webmozart\Assert\Assert;

class PetitionPolicy
{
    public function __construct(
        private readonly UserPermissionService $userPermissionService,
        private readonly Request $request,
    ) {
    }

    public function viewAny(User $user): bool
    {
        $route = $this->request->route();
        Assert::isInstanceOf($route, Route::class);

        $department = $route->parameter('department') ?? $user->lastVisitedDepartment;
        Assert::isInstanceOf($department, Department::class);

        return $this->userPermissionService->hasPermission(Permission::PETITION_READ, $user, $department);
    }

    public function view(User $user, Petition $petition): bool
    {
        if ($this->update($user, $petition)) {
            return true;
        }

        return $this->userPermissionService->hasPermission(Permission::PETITION_READ, $user, $petition->department);
    }

    public function create(User $user): bool
    {
        $route = $this->request->route();
        Assert::isInstanceOf($route, Route::class);

        $department = $route->parameter('department');
        Assert::isInstanceOf($department, Department::class);

        if (!$user->globalRoles->contains('role', 'administrator')) {
            return false;
        }

        return $this->userPermissionService->hasPermission(Permission::PETITION_WRITE, $user, $department);
    }

    public function update(User $user, Petition $petition): bool
    {
        if ($petition->archived_at !== null) {
            return false;
        }

        return $this->userPermissionService->hasPermission(Permission::PETITION_WRITE, $user, $petition->department);
    }
}
