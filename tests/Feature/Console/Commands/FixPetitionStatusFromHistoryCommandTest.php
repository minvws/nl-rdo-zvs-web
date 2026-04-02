<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Enums\PetitionTypeType;
use App\Enums\StatusGroup;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\PetitionType;
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
        PetitionStatusHistory::factory()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $statusB->id,
            'created_at' => now()->subDay()->setTime(12, 0),
            'date' => $date,
        ]);

        $this->artisan('petitions:fix-status-from-history', ['--commit' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'petition_status_id' => $statusB->id,
        ]);
    }

    #[Test]
    public function rollsBackAndReturnsErrorOnException(): void
    {
        $this->createMismatchedPetition();

        $realDb = DB::getFacadeRoot();
        $dbMock = Mockery::mock(DatabaseManager::class);
        $dbMock->shouldReceive("scalar")->once()->andReturn(1);
        $dbMock->shouldReceive("beginTransaction")->once();
        $dbMock->shouldReceive("update")->once()->andThrow(new Exception("Connection lost"));
        $dbMock->shouldReceive("rollBack")->once();
        DB::swap($dbMock);

        try {
            $this->artisan("petitions:fix-status-from-history", ["--commit" => true])
                ->expectsOutputToContain("Error fixing petition statuses: Connection lost")
                ->assertFailed();
        } finally {
            DB::swap($realDb);
        }
    }

    /**
     * Creates a petition with statusA on the record, but a most recent non-future
     * history entry with statusB — producing the mismatch the command is designed to fix.
     *
     * @return array{Petition, PetitionStatus, PetitionStatus}
     */
    private function createMismatchedPetition(): array
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

        $recent = now()->subDay();
        PetitionStatusHistory::factory()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $statusB->id,
            'created_at' => $recent,
            'date' => $recent->format('Y-m-d'),
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
