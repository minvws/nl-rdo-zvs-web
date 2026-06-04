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
use Carbon\CarbonImmutable;
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

        $petition->refresh();
        $this->assertEquals($newStatus->id, $petition->petition_status_id);
    }

    public function testStatusUpdateWithEarlierDateOnlyAddsToHistory(): void
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

        $earlierDate = CarbonImmutable::now()->subDays(10)->toDateString();
        $laterDate = CarbonImmutable::now()->subDays(5)->toDateString();

        $action = $this->app->make(PetitionStatusUpdateAction::class);
        $action->execute($petition, $user, [
            'petition_status_id' => $newStatus->id->toString(),
            'petition_status_date' => $laterDate,
            'petition_status_comment' => null,
        ]);

        $petition->refresh();
        $this->assertEquals($newStatus->id, $petition->petition_status_id);

        $action->execute($petition, $user, [
            'petition_status_id' => $currentStatus->id->toString(),
            'petition_status_date' => $earlierDate,
            'petition_status_comment' => null,
        ]);

        $petition->refresh();
        $this->assertEquals($newStatus->id, $petition->petition_status_id);

        $this->assertDatabaseHas(PetitionStatusHistory::class, [
            'petition_id' => $petition->id,
            'petition_status_id' => $currentStatus->id,
            'date' => $earlierDate,
        ]);
    }

    public function testStatusUpdateWithSameStatusDoesNothing(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $currentStatus = PetitionStatus::factory()->recycle($petitionType)->create();
        $user = User::factory()->create();
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $currentStatus->id,
        ]);

        $historyCountBefore = PetitionStatusHistory::query()
            ->where('petition_id', $petition->id)
            ->count();

        $action = $this->app->make(PetitionStatusUpdateAction::class);
        $action->execute($petition, $user, [
            'petition_status_id' => $currentStatus->id->toString(),
            'petition_status_date' => CarbonImmutable::now()->toDateString(),
            'petition_status_comment' => null,
        ]);

        $historyCountAfter = PetitionStatusHistory::query()
            ->where('petition_id', $petition->id)
            ->count();

        $this->assertEquals($historyCountBefore, $historyCountAfter);
    }

    public function testStatusUpdateWithSameStatusInThePastUpdatesHistoryTable(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $currentStatus = PetitionStatus::factory()->recycle($petitionType)->create();

        $user = User::factory()->create();
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $currentStatus->id,
        ]);

        PetitionStatusHistory::factory()->recycle($petitionType)->recycle($petition)->create([
            'petition_status_id' => PetitionStatus::factory()->recycle($petitionType)->create()->id,
            'date' => CarbonImmutable::now()->subDays(5)->toDateString(),
        ]);

        $historyCountBefore = PetitionStatusHistory::query()
            ->where('petition_id', $petition->id)
            ->count();

        $action = $this->app->make(PetitionStatusUpdateAction::class);

        $action->execute($petition, $user, [
            'petition_status_id' => $currentStatus->id->toString(),
            'petition_status_date' => CarbonImmutable::now()->subDays(10)->toDateString(),
            'petition_status_comment' => null,
        ]);

        $historyCountAfter = PetitionStatusHistory::query()
            ->where('petition_id', $petition->id)
            ->count();

        $this->assertEquals($historyCountBefore + 1, $historyCountAfter);
    }

    public function testStatusUpdateWithNewStatusAndSameDateAsLatestHistory(): void
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

        $historyDate = CarbonImmutable::now()->subDays(5)->toDateString();

        PetitionStatusHistory::factory()->recycle($petition)->recycle($currentStatus)->create([
            'date' => $historyDate,
        ]);

        $action = $this->app->make(PetitionStatusUpdateAction::class);
        $action->execute($petition, $user, [
            'petition_status_id' => $newStatus->id->toString(),
            'petition_status_date' => $historyDate,
            'petition_status_comment' => null,
        ]);

        $petition->refresh();
        $this->assertEquals($newStatus->id, $petition->petition_status_id);

        $this->assertDatabaseHas(PetitionStatusHistory::class, [
            'petition_id' => $petition->id,
            'petition_status_id' => $newStatus->id,
            'date' => $historyDate,
        ]);
    }
}
