<?php

declare(strict_types=1);

namespace Tests\Smoke\Petition;

use App\Enums\AssignmentRole;
use App\Enums\Authorization\DepartmentRole;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Petition;
use App\Models\PetitionAssignment;
use App\Models\User;
use Tests\Smoke\SmokeTestCase;

use function __;

class PetitionAttachPetitionsTest extends SmokeTestCase
{
    public function testAttachPetitionFromSameDepartment(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $department = Department::factory()->create();

        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $relatedPetition = Petition::factory()
            ->recycle($department)
            ->create();

        DepartmentUser::factory()->create([
            'department_id' => $department->id,
            'user_id' => $user->id,
            'role' => DepartmentRole::WRITE,
        ]);

        $this->beUser($user)
            ->visitRoute(RouteName::DEPARTMENTS_PETITION_PETITION_ATTACH_FORM, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertResponseStatus(200)
            ->type($relatedPetition->number, 'number')
            ->press(__('petition.attach'))
            ->assertResponseStatus(200)
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ])
            ->assertResponseStatus(200)
            ->see($relatedPetition->number);
    }

    public function testAttachPetitionFromDifferentDepartment(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $petition = Petition::factory()
            ->recycle($department1)
            ->create();

        $relatedPetition = Petition::factory()
            ->recycle($department2)->create();

        PetitionAssignment::factory()->recycle($relatedPetition)
            ->create([
                'user_id' => $user->id,
                'assignment_role' => AssignmentRole::PRIMARY,
            ]);

        DepartmentUser::factory()->create([
            'department_id' => $department1->id,
            'user_id' => $user->id,
            'role' => DepartmentRole::WRITE,
        ]);

        $this->beUser($user)
            ->visitRoute(RouteName::DEPARTMENTS_PETITION_PETITION_ATTACH_FORM, [
                'department' => $department1->slug,
                'petition' => $petition,
            ])
            ->assertResponseStatus(200)
            ->type($relatedPetition->number, 'number')
            ->press(__('petition.attach'))
            ->assertResponseStatus(200)
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department1->slug,
                'petition' => $petition->id,
            ])
            ->assertResponseStatus(200)
            ->see($relatedPetition->number);
    }
}
