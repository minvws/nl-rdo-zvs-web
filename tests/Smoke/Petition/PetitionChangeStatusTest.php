<?php

declare(strict_types=1);

namespace Tests\Smoke\Petition;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\User;
use Tests\Smoke\SmokeTestCase;

use function __;

class PetitionChangeStatusTest extends SmokeTestCase
{
    public function testCreatePetitionActingAs(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create();

        $originalPetitionStatus = PetitionStatus::factory()
            ->for($petitionType)
            ->create();
        $newPetitionStatus = PetitionStatus::factory()
            ->for($petitionType)
            ->create();

        $petition = Petition::factory()
            ->recycle($department, $originalPetitionStatus, $petitionType)
            ->create();

        DepartmentUser::factory()->create([
            'department_id' => $department->id,
            'user_id' => $user->id,
            'role' => DepartmentRole::WRITE,
        ]);

        $this->beUser($user)
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT, [
                'department' => $department,
                'petition' => $petition->id,
            ])
            ->assertResponseStatus(200)
            ->see($petition->name)
            ->seeIsSelected('petition_status_id', $originalPetitionStatus->id)
            ->select($newPetitionStatus->id, 'petition_status_id')
            ->press(__('general.save'))
            ->assertResponseStatus(200)
            ->see(__('general.saved'));
    }
}
