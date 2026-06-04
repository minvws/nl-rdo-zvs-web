<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition;

use App\Actions\Petition\PetitionStatusHistoryDeleteAction;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\PetitionType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Tests\Feature\FeatureTestCase;

class PetitionStatusHistoryDeleteActionTest extends FeatureTestCase
{
    public function testDeleteStatusHistoryUpdatesPetitionStatus(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $statusA = PetitionStatus::factory()->recycle($petitionType)->create();
        $statusB = PetitionStatus::factory()->recycle($petitionType)->create();
        $user = User::factory()->create();
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $statusB->id,
        ]);

        $historyA = PetitionStatusHistory::factory()->recycle($petition)->recycle($statusA)->create([
            'date' => CarbonImmutable::now()->subDays(10)->toDateString(),
        ]);

        $historyB = PetitionStatusHistory::factory()->recycle($petition)->recycle($statusB)->create([
            'date' => CarbonImmutable::now()->subDays(5)->toDateString(),
        ]);

        $action = $this->app->make(PetitionStatusHistoryDeleteAction::class);
        $action->execute($petition, $user, $historyB);

        $petition->refresh();

        $this->assertEquals($statusA->id, $petition->petition_status_id);
        $this->assertDatabaseMissing(PetitionStatusHistory::class, ['id' => $historyB->id]);
        $this->assertDatabaseHas(PetitionStatusHistory::class, ['id' => $historyA->id]);
    }

    public function testDeleteStatusHistoryCreatesTimelineEntry(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $statusA = PetitionStatus::factory()->recycle($petitionType)->create();
        $statusB = PetitionStatus::factory()->recycle($petitionType)->create();
        $user = User::factory()->create();
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $statusB->id,
        ]);

        PetitionStatusHistory::factory()->recycle($petition)->recycle($statusA)->create([
            'date' => CarbonImmutable::now()->subDays(10)->toDateString(),
        ]);

        $historyB = PetitionStatusHistory::factory()->recycle($petition)->recycle($statusB)->create([
            'date' => CarbonImmutable::now()->subDays(5)->toDateString(),
        ]);

        $action = $this->app->make(PetitionStatusHistoryDeleteAction::class);
        $action->execute($petition, $user, $historyB);

        $timelineItem = $petition->timelineItems()->first();
        $this->assertNotNull($timelineItem);
        $this->assertEquals(TimelineType::STATUS_OCCURRENCE, $timelineItem->type);
        $this->assertEquals($user->id, $timelineItem->user_id);
        $this->assertEquals('Status verwijderd', $timelineItem->data['comment']);
        $this->assertEquals($statusB->status, $timelineItem->data['previous_status']);
        $this->assertEquals($statusA->status, $timelineItem->data['current_status']);
    }

    public function testDeleteLatestStatusHistoryDoesNothing(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $statusA = PetitionStatus::factory()->recycle($petitionType)->create();
        $user = User::factory()->create();
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $statusA->id,
        ]);

        $historyA = PetitionStatusHistory::factory()->recycle($petition)->recycle($statusA)->create([
            'date' => CarbonImmutable::now()->subDays(10)->toDateString(),
        ]);

        $action = $this->app->make(PetitionStatusHistoryDeleteAction::class);
        $action->execute($petition, $user, $historyA);

        $petition->refresh();

        $this->assertEquals($statusA->id, $petition->petition_status_id);
        $this->assertDatabaseHas(PetitionStatusHistory::class, ['id' => $historyA->id]);
    }
}
