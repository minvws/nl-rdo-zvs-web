<?php

declare(strict_types=1);

namespace Tests\Feature\QueryBuilders;

use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use Tests\Feature\FeatureTestCase;

class PetitionStatusQueryBuilderTest extends FeatureTestCase
{
    public function testUsedByDepartmentReturnsOnlyStatusesUsedByPetitionsInDepartment(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $petitionType1 = PetitionType::factory()->recycle($department1)->create();
        $petitionType2 = PetitionType::factory()->recycle($department2)->create();

        $usedStatus1 = PetitionStatus::factory()->recycle($petitionType1)->create(['status' => 'Used Status 1']);
        $usedStatus2 = PetitionStatus::factory()->recycle($petitionType1)->create(['status' => 'Used Status 2']);
        $otherDepartmentStatus = PetitionStatus::factory()->recycle($petitionType2)->create(['status' => 'Other Department Status']);

        PetitionStatus::factory()->recycle($petitionType1)->create(['status' => 'Unused Status']);

        Petition::factory()->recycle($department1)->create(['petition_status_id' => $usedStatus1->id]);
        Petition::factory()->recycle($department1)->create(['petition_status_id' => $usedStatus2->id]);

        Petition::factory()->recycle($department2)->create(
            ['petition_status_id' => $otherDepartmentStatus->id],
        );
        $result = PetitionStatus::query()->usedByDepartment($department1)->get();

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('status', 'Used Status 1'));
        $this->assertTrue($result->contains('status', 'Used Status 2'));
        $this->assertFalse($result->contains('status', 'Unused Status'));
        $this->assertFalse($result->contains('status', 'Other Department Status'));
    }

    public function testUsedByDepartmentOrdersByStatus(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();

        $statusZ = PetitionStatus::factory()->recycle($petitionType)->create(['status' => 'Z Status']);
        $statusA = PetitionStatus::factory()->recycle($petitionType)->create(['status' => 'A Status']);
        $statusM = PetitionStatus::factory()->recycle($petitionType)->create(['status' => 'M Status']);

        Petition::factory()->recycle($department)->create(['petition_status_id' => $statusZ->id]);
        Petition::factory()->recycle($department)->create(['petition_status_id' => $statusA->id]);
        Petition::factory()->recycle($department)->create(['petition_status_id' => $statusM->id]);

        $result = PetitionStatus::query()->usedByDepartment($department)->get();

        $this->assertEquals('A Status', $result->first()->status);
        $this->assertEquals('M Status', $result->get(1)->status);
        $this->assertEquals('Z Status', $result->last()->status);
    }

    public function testUsedByDepartmentGroupsByStatusToAvoidDuplicates(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();

        $status = PetitionStatus::factory()->recycle($petitionType)->create(['status' => 'Shared Status']);

        Petition::factory()->recycle($department)->create(['petition_status_id' => $status->id]);
        Petition::factory()->recycle($department)->create(['petition_status_id' => $status->id]);
        Petition::factory()->recycle($department)->create(['petition_status_id' => $status->id]);

        $result = PetitionStatus::query()->usedByDepartment($department)->get();

        $this->assertCount(1, $result);
        $this->assertEquals('Shared Status', $result->first()->status);
    }

    public function testUsedByDepartmentReturnsEmptyWhenNoPetitionsHaveStatus(): void
    {
        $department = Department::factory()->create();

        $result = PetitionStatus::query()->usedByDepartment($department)->get();

        $this->assertCount(0, $result);
    }
}
