<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Enums\PetitionTypeType;
use App\Enums\StatusGroup;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\PetitionType;
use App\Models\TimelineItem;
use App\Models\User;
use Exception;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function now;

class FixPetitionStatusFromHistoryCommandTest extends FeatureTestCase
{
    #[Test]
    public function dryRunShowsMismatchCountWithoutUpdatingDatabase(): void
    {
        [$petition] = $this->createMismatchedPetition();

        $this->artisan('petitions:fix-status-from-history')
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('Found 1 petition(s) with a mismatched status.')
            ->expectsOutputToContain('Current status (petitions)')
            ->expectsOutputToContain($petition->number)
            ->expectsOutputToContain('Would update 1 petition(s). Run with --commit to apply changes.')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'petition_status_id' => $petition->petition_status_id,
        ]);
    }

    #[Test]
    public function commitFixesMismatchedPetitionStatus(): void
    {
        [$petition, , $statusB] = $this->createMismatchedPetition();

        $this->artisan('petitions:fix-status-from-history', ['--commit' => true])
            ->expectsOutputToContain('Found 1 petition(s) with a mismatched status.')
            ->expectsOutputToContain($petition->number)
            ->expectsOutputToContain('Successfully updated 1 petition(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'petition_status_id' => $statusB->id,
        ]);
    }

    #[Test]
    public function dryRunReturnsSuccessWhenNoMismatchesFound(): void
    {
        $this->artisan('petitions:fix-status-from-history')
            ->expectsOutputToContain('No mismatched petitions found. Nothing to update.')
            ->assertSuccessful();
    }

    #[Test]
    public function commitReturnsSuccessWhenNoMismatchesFound(): void
    {
        $this->artisan('petitions:fix-status-from-history', ['--commit' => true])
            ->expectsOutputToContain('No mismatched petitions found. Nothing to update.')
            ->assertSuccessful();
    }

    #[Test]
    public function doesNotModifyAlreadyMatchingPetitions(): void
    {
        [$mismatchedPetition, , $statusB] = $this->createMismatchedPetition();
        $matchingPetition = $this->createMatchingPetition($statusB);

        $this->artisan('petitions:fix-status-from-history', ['--commit' => true])
            ->expectsOutputToContain('Found 1 petition(s) with a mismatched status.')
            ->expectsOutputToContain('Successfully updated 1 petition(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $mismatchedPetition->id,
            'petition_status_id' => $statusB->id,
        ]);

        $this->assertDatabaseHas('petitions', [
            'id' => $matchingPetition->id,
            'petition_status_id' => $statusB->id,
        ]);
    }

    #[Test]
    public function ignoresFutureHistoryEntries(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => PetitionTypeType::WOO_VERZOEK->value,
        ]);
        $statusA = PetitionStatus::factory()->recycle($petitionType)->create([
            'order' => 1,
            'status_group' => StatusGroup::PENDING,
        ]);
        $statusB = PetitionStatus::factory()->recycle($petitionType)->create([
            'order' => 2,
            'status_group' => StatusGroup::FINISHED,
        ]);

        $petition = Petition::factory()
            ->recycle([$department, $petitionType])
            ->create(['petition_status_id' => $statusA->id]);

        // Only a future history entry with statusB — should be ignored by the command
        $future = now()->addDay();
        PetitionStatusHistory::factory()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $statusB->id,
            'created_at' => $future,
            'date' => $future->format('Y-m-d'),
        ]);

        $this->artisan('petitions:fix-status-from-history', ['--commit' => true])
            ->expectsOutputToContain('No mismatched petitions found. Nothing to update.')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'petition_status_id' => $statusA->id,
        ]);
    }

    #[Test]
    public function mostRecentNonFutureHistoryEntryWinsOnSameDate(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => PetitionTypeType::WOO_VERZOEK->value,
        ]);
        $statusA = PetitionStatus::factory()->recycle($petitionType)->create([
            'order' => 1,
            'status_group' => StatusGroup::PENDING,
        ]);
        $statusB = PetitionStatus::factory()->recycle($petitionType)->create([
            'order' => 2,
            'status_group' => StatusGroup::FINISHED,
        ]);

        $petition = Petition::factory()
            ->recycle([$department, $petitionType])
            ->create(['petition_status_id' => $statusA->id]);

        $date = now()->subDay()->format('Y-m-d');

        // Two entries on the same date — statusB was created later and should win
        PetitionStatusHistory::factory()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $statusA->id,
            'created_at' => now()->subDay()->setTime(8, 0),
            'date' => $date,
        ]);
        TimelineItem::factory()->create([
            'timelineable_id' => $petition->id->toString(),
            'timelineable_type' => 'petition',
            'type' => TimelineType::STATUS_OCCURRENCE,
            'data' => ['current_status' => $statusA->status],
            'created_at' => now()->subDay()->setTime(8, 0),
            'updated_at' => now()->subDay()->setTime(8, 0),
            'user_id' => User::factory()->create()->id->toString(),
        ]);

        PetitionStatusHistory::factory()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $statusB->id,
            'created_at' => now()->subDay()->setTime(12, 0),
            'date' => $date,
        ]);
        TimelineItem::factory()->create([
            'timelineable_id' => $petition->id->toString(),
            'timelineable_type' => 'petition',
            'type' => TimelineType::STATUS_OCCURRENCE,
            'data' => ['current_status' => $statusB->status],
            'created_at' => now()->subDay()->setTime(12, 0),
            'updated_at' => now()->subDay()->setTime(12, 0),
            'user_id' => User::factory()->create()->id->toString(),
        ]);

        $this->artisan('petitions:fix-status-from-history', ['--commit' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'petition_status_id' => $statusB->id,
        ]);
    }

    #[Test]
    public function doesNotChangePetitionWithNoHistoryEntries(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => PetitionTypeType::WOO_VERZOEK->value,
        ]);
        $status = PetitionStatus::factory()->recycle($petitionType)->create([
            'order' => 1,
            'status_group' => StatusGroup::PENDING,
        ]);

        $petition = Petition::factory()
            ->recycle([$department, $petitionType])
            ->create(['petition_status_id' => $status->id]);

        $this->artisan('petitions:fix-status-from-history', ['--commit' => true])
            ->expectsOutputToContain('No mismatched petitions found. Nothing to update.')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'petition_status_id' => $status->id,
        ]);
    }

    #[Test]
    public function eindUitspraakTimelineItemMatchesUitspraakHistoryEntry(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => PetitionTypeType::WOO_VERZOEK->value,
        ]);
        $statusZitting = PetitionStatus::factory()->recycle($petitionType)->create([
            'status' => 'Zitting',
            'order' => 1,
            'status_group' => StatusGroup::PENDING,
        ]);
        $statusUitspraak = PetitionStatus::factory()->recycle($petitionType)->create([
            'status' => 'Uitspraak',
            'order' => 2,
            'status_group' => StatusGroup::CLOSED,
        ]);

        // Petition is currently stuck at Zitting
        $petition = Petition::factory()
            ->recycle([$department, $petitionType])
            ->create(['petition_status_id' => $statusZitting->id]);

        $user = User::factory()->create();

        // Older history entry for Zitting — has matching 'Zitting' timeline item
        PetitionStatusHistory::factory()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $statusZitting->id,
            'created_at' => now()->subDays(10),
            'date' => now()->subDays(10)->format('Y-m-d'),
        ]);
        TimelineItem::factory()->create([
            'timelineable_id' => $petition->id->toString(),
            'timelineable_type' => 'petition',
            'type' => TimelineType::STATUS_OCCURRENCE,
            'data' => ['current_status' => 'Zitting'],
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
            'user_id' => $user->id->toString(),
        ]);

        // Newer history entry for Uitspraak — no 'Uitspraak' timeline item,
        // but has an 'Eind uitspraak' timeline item (old substatus of Uitspraak)
        PetitionStatusHistory::factory()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $statusUitspraak->id,
            'created_at' => now()->subDays(2),
            'date' => now()->subDays(2)->format('Y-m-d'),
        ]);
        TimelineItem::factory()->create([
            'timelineable_id' => $petition->id->toString(),
            'timelineable_type' => 'petition',
            'type' => TimelineType::STATUS_OCCURRENCE,
            'data' => ['current_status' => 'Eind uitspraak'],
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
            'user_id' => $user->id->toString(),
        ]);

        $this->artisan('petitions:fix-status-from-history', ['--commit' => true])
            ->expectsOutputToContain('Successfully updated 1 petition(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'petition_status_id' => $statusUitspraak->id,
        ]);
    }

    #[Test]
    public function doesNotUpdateWhenPetitionAlreadyAtUitspraakAndTimelineHasEindUitspraak(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => PetitionTypeType::WOO_VERZOEK->value,
        ]);
        $statusZitting = PetitionStatus::factory()->recycle($petitionType)->create([
            'status' => 'Zitting',
            'order' => 1,
            'status_group' => StatusGroup::PENDING,
        ]);
        $statusUitspraak = PetitionStatus::factory()->recycle($petitionType)->create([
            'status' => 'Uitspraak',
            'order' => 2,
            'status_group' => StatusGroup::CLOSED,
        ]);

        // Petition is already correctly at Uitspraak
        $petition = Petition::factory()
            ->recycle([$department, $petitionType])
            ->create(['petition_status_id' => $statusUitspraak->id]);

        $user = User::factory()->create();

        // Older Zitting history entry with matching timeline item
        PetitionStatusHistory::factory()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $statusZitting->id,
            'created_at' => now()->subDays(10),
            'date' => now()->subDays(10)->format('Y-m-d'),
        ]);
        TimelineItem::factory()->create([
            'timelineable_id' => $petition->id->toString(),
            'timelineable_type' => 'petition',
            'type' => TimelineType::STATUS_OCCURRENCE,
            'data' => ['current_status' => 'Zitting'],
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
            'user_id' => $user->id->toString(),
        ]);

        // Newer Uitspraak history entry with only an 'Eind uitspraak' timeline item
        // (no direct 'Uitspraak' timeline item — old substatus pattern)
        PetitionStatusHistory::factory()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $statusUitspraak->id,
            'created_at' => now()->subDays(2),
            'date' => now()->subDays(2)->format('Y-m-d'),
        ]);
        TimelineItem::factory()->create([
            'timelineable_id' => $petition->id->toString(),
            'timelineable_type' => 'petition',
            'type' => TimelineType::STATUS_OCCURRENCE,
            'data' => ['current_status' => 'Eind uitspraak'],
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
            'user_id' => $user->id->toString(),
        ]);

        $this->artisan('petitions:fix-status-from-history', ['--commit' => true])
            ->expectsOutputToContain('No mismatched petitions found. Nothing to update.')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'petition_status_id' => $statusUitspraak->id,
        ]);
    }

    #[Test]
    public function doesNotReplaceIntakeWithToebedeling(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => PetitionTypeType::WOO_VERZOEK->value,
        ]);
        $statusIntake = PetitionStatus::factory()->recycle($petitionType)->create([
            'status' => 'Intake',
            'order' => 1,
            'status_group' => StatusGroup::PENDING,
        ]);
        $statusToebedeling = PetitionStatus::factory()->recycle($petitionType)->create([
            'status' => 'Toebedeling',
            'order' => 2,
            'status_group' => StatusGroup::PENDING,
        ]);

        $petition = Petition::factory()
            ->recycle([$department, $petitionType])
            ->create(['petition_status_id' => $statusIntake->id]);

        $recent = now()->subDay();
        PetitionStatusHistory::factory()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $statusToebedeling->id,
            'created_at' => $recent,
            'date' => $recent->format('Y-m-d'),
        ]);
        TimelineItem::factory()->create([
            'timelineable_id' => $petition->id->toString(),
            'timelineable_type' => 'petition',
            'type' => TimelineType::STATUS_OCCURRENCE,
            'data' => ['current_status' => 'Toebedeling'],
            'created_at' => $recent,
            'updated_at' => $recent,
            'user_id' => User::factory()->create()->id->toString(),
        ]);

        $this->artisan('petitions:fix-status-from-history', ['--commit' => true])
            ->expectsOutputToContain('No mismatched petitions found. Nothing to update.')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'petition_status_id' => $statusIntake->id,
        ]);
    }

    #[Test]
    public function doesNotUpdateWhenStatusNameMatchesEvenIfUuidDiffers(): void
    {
        // Simulates a data-migration scenario where a petition_status was re-seeded
        // with a new UUID but the same name. The petition still references the old UUID,
        // the history entry references the new UUID. No visible status change should occur.
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => PetitionTypeType::WOO_VERZOEK->value,
        ]);

        $oldStatus = PetitionStatus::factory()->recycle($petitionType)->create([
            'status' => 'Bezwaar ingetrokken',
            'order' => 1,
            'status_group' => StatusGroup::CLOSED,
        ]);
        $newStatus = PetitionStatus::factory()->recycle($petitionType)->create([
            'status' => 'Bezwaar ingetrokken',
            'order' => 1,
            'status_group' => StatusGroup::CLOSED,
        ]);

        // Petition points to the old UUID
        $petition = Petition::factory()
            ->recycle([$department, $petitionType])
            ->create(['petition_status_id' => $oldStatus->id]);

        // History entry points to the new UUID, with a matching timeline item
        $recent = now()->subDay();
        PetitionStatusHistory::factory()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $newStatus->id,
            'created_at' => $recent,
            'date' => $recent->format('Y-m-d'),
        ]);
        TimelineItem::factory()->create([
            'timelineable_id' => $petition->id->toString(),
            'timelineable_type' => 'petition',
            'type' => TimelineType::STATUS_OCCURRENCE,
            'data' => ['current_status' => 'Bezwaar ingetrokken'],
            'created_at' => $recent,
            'updated_at' => $recent,
            'user_id' => User::factory()->create()->id->toString(),
        ]);

        $this->artisan('petitions:fix-status-from-history', ['--commit' => true])
            ->expectsOutputToContain('No mismatched petitions found. Nothing to update.')
            ->assertSuccessful();

        // Petition still points to the old UUID — name-based match, no update needed
        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'petition_status_id' => $oldStatus->id,
        ]);
    }

    #[Test]
    public function doesNotChangePetitionWhenHistoryEntryHasNoTimelineItem(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => PetitionTypeType::WOO_VERZOEK->value,
        ]);
        $statusA = PetitionStatus::factory()->recycle($petitionType)->create([
            'order' => 1,
            'status_group' => StatusGroup::PENDING,
        ]);
        $statusB = PetitionStatus::factory()->recycle($petitionType)->create([
            'order' => 2,
            'status_group' => StatusGroup::FINISHED,
        ]);

        $petition = Petition::factory()
            ->recycle([$department, $petitionType])
            ->create(['petition_status_id' => $statusA->id]);

        // History entry exists but with no corresponding timeline item (e.g. imported/backdated)
        $recent = now()->subDay();
        PetitionStatusHistory::factory()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $statusB->id,
            'created_at' => $recent,
            'date' => $recent->format('Y-m-d'),
        ]);

        $this->artisan('petitions:fix-status-from-history', ['--commit' => true])
            ->expectsOutputToContain('No mismatched petitions found. Nothing to update.')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'petition_status_id' => $statusA->id,
        ]);
    }

    #[Test]
    public function zaaknummerArgumentShowsDetailForSinglePetitionWithoutCommitting(): void
    {
        [$petition] = $this->createMismatchedPetition();

        $this->artisan('petitions:fix-status-from-history', ['zaaknummer' => $petition->number])
            ->expectsOutputToContain('SINGLE PETITION MODE')
            ->expectsOutputToContain($petition->number)
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'petition_status_id' => $petition->petition_status_id,
        ]);
    }

    #[Test]
    public function zaaknummerArgumentShowsNothingWhenPetitionStatusMatches(): void
    {
        [, , $statusB] = $this->createMismatchedPetition();
        $matchingPetition = $this->createMatchingPetition($statusB);

        $this->artisan('petitions:fix-status-from-history', ['zaaknummer' => $matchingPetition->number])
            ->expectsOutputToContain('SINGLE PETITION MODE')
            ->expectsOutputToContain('No mismatched petitions found. Nothing to update.')
            ->assertSuccessful();
    }

    #[Test]
    public function skipsNumbersOnSkipList(): void
    {
        [$petition, $statusA] = $this->createMismatchedPetition(number: '2022.041');

        $this->artisan('petitions:fix-status-from-history', ['--commit' => true])
            ->expectsOutputToContain('No mismatched petitions found. Nothing to update.')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'petition_status_id' => $statusA->id,
        ]);
    }

    #[Test]
    public function rollsBackAndReturnsErrorOnException(): void
    {
        $this->createMismatchedPetition();

        $realDb = DB::getFacadeRoot();
        $dbMock = Mockery::mock(DatabaseManager::class);
        $dbMock->shouldReceive('scalar')->once()->andReturn(1);
        $dbMock->shouldReceive('select')->once()->andReturn([]);
        $dbMock->shouldReceive('beginTransaction')->once();
        $dbMock->shouldReceive('update')->once()->andThrow(new Exception('Connection lost'));
        $dbMock->shouldReceive('rollBack')->once();
        DB::swap($dbMock);

        try {
            $this->artisan('petitions:fix-status-from-history', ['--commit' => true])
                ->expectsOutputToContain('Error fixing petition statuses: Connection lost')
                ->assertFailed();
        } finally {
            DB::swap($realDb);
        }
    }

    /**
     * Creates a petition with statusA on the record, but a most recent non-future
     * history entry (with a matching timeline item) with statusB — producing the
     * mismatch the command is designed to fix.
     *
     * @return array{Petition, PetitionStatus, PetitionStatus}
     */
    private function createMismatchedPetition(?string $number = null): array
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => PetitionTypeType::WOO_VERZOEK->value,
        ]);
        $statusA = PetitionStatus::factory()->recycle($petitionType)->create([
            'order' => 1,
            'status_group' => StatusGroup::PENDING,
        ]);
        $statusB = PetitionStatus::factory()->recycle($petitionType)->create([
            'order' => 2,
            'status_group' => StatusGroup::FINISHED,
        ]);

        $attributes = ['petition_status_id' => $statusA->id];
        if ($number !== null) {
            $attributes['number'] = $number;
        }

        $petition = Petition::factory()
            ->recycle([$department, $petitionType])
            ->create($attributes);

        $recent = now()->subDay();
        PetitionStatusHistory::factory()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $statusB->id,
            'created_at' => $recent,
            'date' => $recent->format('Y-m-d'),
        ]);
        TimelineItem::factory()->create([
            'timelineable_id' => $petition->id->toString(),
            'timelineable_type' => 'petition',
            'type' => TimelineType::STATUS_OCCURRENCE,
            'data' => ['current_status' => $statusB->status],
            'created_at' => $recent,
            'updated_at' => $recent,
            'user_id' => User::factory()->create()->id->toString(),
        ]);

        return [$petition, $statusA, $statusB];
    }

    /**
     * Creates a petition whose petition_status_id already matches its most recent history entry.
     */
    private function createMatchingPetition(PetitionStatus $status): Petition
    {
        $petition = Petition::factory()
            ->recycle($status->petitionType)
            ->create(['petition_status_id' => $status->id]);

        $recent = now()->subDay();
        PetitionStatusHistory::factory()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $status->id,
            'created_at' => $recent,
            'date' => $recent->format('Y-m-d'),
        ]);

        return $petition;
    }
}
