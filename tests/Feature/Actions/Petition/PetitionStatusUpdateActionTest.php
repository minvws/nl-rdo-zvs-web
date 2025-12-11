<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition;

use App\Actions\Petition\PetitionStatusUpdateAction;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\PetitionType;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class PetitionStatusUpdateActionTest extends FeatureTestCase
{
    public function testStatusUpdate(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $currentStatus = PetitionStatus::factory()->recycle($petitionType)->create();
        $newStatus = PetitionStatus::factory()->recycle($petitionType)->create();
        $user = User::factory()->create();
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $currentStatus->id,
        ]);
        $petitionStatusDate = $this->faker->calendarDate();

        $action = $this->app->make(PetitionStatusUpdateAction::class);
        $action->execute($petition, $user, [
            'petition_status_id' => $newStatus->id->toString(),
            'petition_status_date' => $petitionStatusDate->toDateString(),
            'petition_status_comment' => 'Status changed for testing purposes',
        ]);

        $this->assertDatabaseHas(PetitionStatusHistory::class, [
            'petition_id' => $petition->id,
            'petition_status_id' => $newStatus->id,
            'date' => $petitionStatusDate,
        ]);
    }
}
