<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Authorization\Permission;
use App\Models\Decision;
use App\Models\Department;
use App\Models\User;
use App\Services\Authorisation\UserPermissionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Webmozart\Assert\Assert;

class DecisionPolicy
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

        $department = $route->parameter('department');
        Assert::isInstanceOf($department, Department::class);

        return $this->userPermissionService->hasPermission(Permission::DECISION_READ, $user, $department);
    }

    public function view(User $user, Decision $decision): bool
    {
        if ($this->update($user, $decision)) {
            return true;
        }

        return $this->userPermissionService->hasPermission(Permission::DECISION_READ, $user, $decision->department);
    }

    public function create(User $user): bool
    {
        $route = $this->request->route();
        Assert::isInstanceOf($route, Route::class);

        $department = $route->parameter('department');
        Assert::isInstanceOf($department, Department::class);

        return $this->userPermissionService->hasPermission(Permission::DECISION_WRITE, $user, $department);
    }

    public function update(User $user, Decision $decision): bool
    {
        if ($decision->archived_at !== null) {
            return false;
        }

        return $this->userPermissionService->hasPermission(Permission::DECISION_WRITE, $user, $decision->department);
    }

    public function unarchive(User $user, Decision $decision): bool
    {
        if ($decision->archived_at === null) {
            return false;
        }

        return $this->userPermissionService->hasPermission(Permission::DECISION_WRITE, $user, $decision->department);
    }
}
