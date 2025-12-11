<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Decision;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Decision;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DecisionArchivedRouteSecurityTest extends FeatureTestCase
{
    #[Test]
    public function testCannotAccessEditForArchivedDecision(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create([
            'archived_at' => Carbon::now(),
        ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_EDIT, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function testCanAccessEditForNonArchivedDecision(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)->fullyVerified()->create();

        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_EDIT, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertOk();
    }

    #[Test]
    public function testCannotUpdateArchivedDecision(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create([
            'archived_at' => Carbon::now(),
        ]);
        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();


        $this->beUser($user)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_UPDATE, [
                'department' => $department,
                'decision' => $decision,
            ], [
                'name' => 'Updated Decision Name',
                'reference' => 'UPDATED-REF',
                'date' => '2025-07-16',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function testCanUpdateNonArchivedDecision(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($user)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_UPDATE, [
                'department' => $department,
                'decision' => $decision,
            ], [
                'name' => 'Updated Decision Name',
                'reference' => 'UPDATED-REF',
                'date' => '2025-07-16',
            ])
            ->assertRedirect();
    }
}
