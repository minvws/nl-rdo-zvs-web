<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\AssignmentRole;
use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionAssignment;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class PetitionAssignedSecondaryUserControllerTest extends FeatureTestCase
{
    public function testShowAssignedSecondaryUser(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk();
    }

    public function testEditAssignedSecondaryUser(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk();
    }

    public function testUpdateAssignedSecondaryUser(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $primaryUser = User::factory()->recycle($department)->create();
        $secondaryUser = User::factory()->recycle($department)->create();

        PetitionAssignment::factory()->create([
            'petition_id' => $petition->id,
            'user_id' => $primaryUser->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => $secondaryUser->id->toString(),
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ]);

        $petition->refresh();
        // Now using the assignments() relation
        $secondAssignment = $petition->assignments()->where('assignment_role', AssignmentRole::SECONDARY)->first();
        $this->assertEquals($secondaryUser->id, $secondAssignment?->user_id);
    }

    public function testUpdateAssignedSecondaryUserWithHtmx(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $primaryUser = User::factory()->recycle($department)->create();
        $secondaryUser = User::factory()->recycle($department)->create();

        PetitionAssignment::factory()->create([
            'petition_id' => $petition->id,
            'user_id' => $primaryUser->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => $secondaryUser->id->toString(),
            ])
            ->assertOk();

        $petition->refresh();
        // Now using the assignments() relation
        $secondAssignment = $petition->assignments()->where('assignment_role', AssignmentRole::SECONDARY)->first();
        $this->assertEquals($secondaryUser->id, $secondAssignment?->user_id);
    }

    public function testUpdateAssignedSecondaryUserAllowsNull(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $primaryUser = User::factory()->recycle($department)->create();

        PetitionAssignment::factory()->create([
            'petition_id' => $petition->id,
            'user_id' => $primaryUser->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => null,
            ])
            ->assertSessionHasNoErrors();

        $petition->refresh();
        $secondAssignment = $petition->assignments()->where('assignment_role', AssignmentRole::SECONDARY)->first();
        $this->assertNull($secondAssignment);
    }

    public function testUpdateAssignedSecondaryUserToNullWithHtmxShowsEmptyState(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $primaryUser = User::factory()->recycle($department)->create();
        $secondaryUser = User::factory()->recycle($department)->create();

        PetitionAssignment::factory()->create([
            'petition_id' => $petition->id,
            'user_id' => $primaryUser->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        PetitionAssignment::factory()->create([
            'petition_id' => $petition->id,
            'user_id' => $secondaryUser->id,
            'assignment_role' => AssignmentRole::SECONDARY,
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => null,
            ])
            ->assertOk()
            ->assertDontSee($secondaryUser->name)
            ->assertSee('-');

        $petition->refresh();
        $secondAssignment = $petition->assignments()->where('assignment_role', AssignmentRole::SECONDARY)->first();
        $this->assertNull($secondAssignment);
    }

    public function testUpdateAssignedSecondaryUserWithoutPermission(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $user = User::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => $user->id->toString(),
            ])
            ->assertForbidden();
    }

    public function testUpdateAssignedSecondaryUserVerifiesSortOrders(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $primaryUser = User::factory()->recycle($department)->create();
        $secondaryUser = User::factory()->recycle($department)->create();

        PetitionAssignment::factory()->create([
            'petition_id' => $petition->id,
            'user_id' => $primaryUser->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => $secondaryUser->id->toString(),
            ]);

        $petition->refresh();
        $primaryAssignment = $petition->assignments()->where('assignment_role', AssignmentRole::PRIMARY)->first();
        $secondaryAssignment = $petition->assignments()->where('assignment_role', AssignmentRole::SECONDARY)->first();

        $this->assertEquals(AssignmentRole::PRIMARY, $primaryAssignment->assignment_role);
        $this->assertEquals(AssignmentRole::SECONDARY, $secondaryAssignment->assignment_role);
        $this->assertEquals($primaryUser->id, $primaryAssignment->user_id);
        $this->assertEquals($secondaryUser->id, $secondaryAssignment->user_id);
    }

    public function testUpdateAssignedSecondaryUserSameAsPrimaryReturnsError(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $sameUser = User::factory()->recycle($department)->create();

        PetitionAssignment::factory()->create([
            'petition_id' => $petition->id,
            'user_id' => $sameUser->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => $sameUser->id->toString(),
            ])
            ->assertSessionHasErrors('user_id');
    }

    public function testUpdateAssignedSecondaryUserSameAsPrimaryWithHxTargetBodyReturnsError(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $sameUser = User::factory()->recycle($department)->create();

        PetitionAssignment::factory()->create([
            'petition_id' => $petition->id,
            'user_id' => $sameUser->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => $sameUser->id->toString(),
                'hx-target' => '#some-target',
            ])
            ->assertSessionHasErrors('user_id');
    }
}
