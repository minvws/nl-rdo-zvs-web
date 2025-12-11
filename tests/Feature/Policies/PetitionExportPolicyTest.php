<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Enums\Authorization\DepartmentRole;
use App\Models\Department;
use App\Models\PetitionExport;
use App\Models\User;
use App\Policies\PetitionExportPolicy;
use App\Services\Authorisation\UserPermissionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\Feature\FeatureTestCase;

class PetitionExportPolicyTest extends FeatureTestCase
{
    public function testViewAnyAllowsUserWithPetitionReadPermission(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->fullyVerified()->withDepartmentRoles($department, DepartmentRole::READ)->create();

        $request = new Request();
        $route = new Route(['GET'], '/test', []);
        $route->bind($request);
        $route->setParameter('department', $department);
        $request->setRouteResolver(fn() => $route);

        $userPermissionService = $this->app->make(UserPermissionService::class);
        $policy = new PetitionExportPolicy($userPermissionService, $request);

        $this->assertTrue($policy->viewAny($user));
    }

    public function testViewAnyDeniesUserWithoutPetitionReadPermission(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        $request = new Request();
        $route = new Route(['GET'], '/test', []);
        $route->bind($request);
        $route->setParameter('department', $department);
        $request->setRouteResolver(fn() => $route);

        $userPermissionService = $this->app->make(UserPermissionService::class);
        $policy = new PetitionExportPolicy($userPermissionService, $request);

        $this->assertFalse($policy->viewAny($user));
    }

    public function testViewAllowsUserWithPetitionReadPermissionForExportDepartment(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->fullyVerified()->withDepartmentRoles($department, DepartmentRole::READ)->create();
        $export = PetitionExport::factory()->recycle($department)->create();

        $userPermissionService = $this->app->make(UserPermissionService::class);
        $policy = new PetitionExportPolicy($userPermissionService, new Request());

        $this->assertTrue($policy->view($user, $export));
    }

    public function testViewDeniesUserWithoutPetitionReadPermissionForExportDepartment(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $export = PetitionExport::factory()->recycle($department)->create();

        $userPermissionService = $this->app->make(UserPermissionService::class);
        $policy = new PetitionExportPolicy($userPermissionService, new Request());

        $this->assertFalse($policy->view($user, $export));
    }

    public function testCreateAllowsUserWithPetitionWritePermission(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->fullyVerified()->withDepartmentRoles($department, DepartmentRole::WRITE)->create();

        $request = new Request();
        $route = new Route(['POST'], '/test', []);
        $route->bind($request);
        $route->setParameter('department', $department);
        $request->setRouteResolver(fn() => $route);

        $userPermissionService = $this->app->make(UserPermissionService::class);
        $policy = new PetitionExportPolicy($userPermissionService, $request);

        $this->assertTrue($policy->create($user));
    }

    public function testCreateDeniesUserWithoutPetitionWritePermission(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->fullyVerified()->withDepartmentRoles($department, DepartmentRole::READ)->create();

        $request = new Request();
        $route = new Route(['POST'], '/test', []);
        $route->bind($request);
        $route->setParameter('department', $department);
        $request->setRouteResolver(fn() => $route);

        $userPermissionService = $this->app->make(UserPermissionService::class);
        $policy = new PetitionExportPolicy($userPermissionService, $request);

        $this->assertFalse($policy->create($user));
    }

    public function testUpdateAllowsUserWithPetitionWritePermissionForExportDepartment(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->fullyVerified()->withDepartmentRoles($department, DepartmentRole::WRITE)->create();
        $export = PetitionExport::factory()->recycle($department)->create();

        $userPermissionService = $this->app->make(UserPermissionService::class);
        $policy = new PetitionExportPolicy($userPermissionService, new Request());

        $this->assertTrue($policy->update($user, $export));
    }

    public function testUpdateDeniesUserWithoutPetitionWritePermissionForExportDepartment(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->fullyVerified()->withDepartmentRoles($department, DepartmentRole::READ)->create();
        $export = PetitionExport::factory()->recycle($department)->create();

        $userPermissionService = $this->app->make(UserPermissionService::class);
        $policy = new PetitionExportPolicy($userPermissionService, new Request());

        $this->assertFalse($policy->update($user, $export));
    }

    public function testDeleteAllowsUserWithPetitionWritePermissionForExportDepartment(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->fullyVerified()->withDepartmentRoles($department, DepartmentRole::WRITE)->create();

        $export = PetitionExport::factory()->recycle($department)->create();

        $userPermissionService = $this->app->make(UserPermissionService::class);
        $policy = new PetitionExportPolicy($userPermissionService, new Request());

        $this->assertTrue($policy->delete($user, $export));
    }

    public function testDeleteDeniesUserWithoutPetitionWritePermissionForExportDepartment(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->fullyVerified()->withDepartmentRoles($department, DepartmentRole::READ)->create();
        $export = PetitionExport::factory()->recycle($department)->create();

        $userPermissionService = $this->app->make(UserPermissionService::class);
        $policy = new PetitionExportPolicy($userPermissionService, new Request());

        $this->assertFalse($policy->delete($user, $export));
    }
}
