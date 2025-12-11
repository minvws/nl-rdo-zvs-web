<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Authorization\Permission;
use App\Models\Department;
use App\Models\PetitionExport;
use App\Models\User;
use App\Services\Authorisation\UserPermissionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Webmozart\Assert\Assert;

class PetitionExportPolicy
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

        return $this->userPermissionService->hasPermission(Permission::PETITION_READ, $user, $department);
    }

    public function view(User $user, PetitionExport $petitionExport): bool
    {
        return $this->userPermissionService->hasPermission(Permission::PETITION_READ, $user, $petitionExport->department);
    }

    public function create(User $user): bool
    {
        $route = $this->request->route();
        Assert::isInstanceOf($route, Route::class);

        $department = $route->parameter('department');
        Assert::isInstanceOf($department, Department::class);

        return $this->userPermissionService->hasPermission(Permission::PETITION_WRITE, $user, $department);
    }

    public function update(User $user, PetitionExport $petitionExport): bool
    {
        return $this->userPermissionService->hasPermission(Permission::PETITION_WRITE, $user, $petitionExport->department);
    }

    public function delete(User $user, PetitionExport $petitionExport): bool
    {
        return $this->userPermissionService->hasPermission(Permission::PETITION_WRITE, $user, $petitionExport->department);
    }
}
