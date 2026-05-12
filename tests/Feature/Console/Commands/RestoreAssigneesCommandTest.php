<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\Petition\RestoreAssigneesCommand;
use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\TimelineItem;
use App\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\Feature\FeatureTestCase;

use function now;

#[CoversClass(RestoreAssigneesCommand::class)]
final class RestoreAssigneesCommandTest extends FeatureTestCase
{
    #[Test]
    public function testDryRunWithNoAssignmentOccurrences(): void
    {
        Petition::factory()->create();

        $this->artisan('petitions:restore-assignees')
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('No petitions found')
            ->assertSuccessful();
    }

    #[Test]
    public function testDryRunWithPetitiesAlreadyCorrect(): void
    {
        $user = User::factory()->create();
        $petition = Petition::factory()->create(['assigned_to' => $user->id]);

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::ASSIGNMENT_OCCURRENCE,
            'data' => ['current_assigned_user_id' => $user->id->toString()],
        ]);

        $this->artisan('petitions:restore-assignees')
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('Would update 0 petition(s)')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'assigned_to' => $user->id,
        ]);
    }

    #[Test]
    public function testDryRunWithPetitiesToUpdate(): void
    {
        $originalUser = User::factory()->create();
        $correctUser = User::factory()->create();
        $petition = Petition::factory()->create(['assigned_to' => $originalUser->id]);

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::ASSIGNMENT_OCCURRENCE,
            'data' => ['current_assigned_user_id' => $correctUser->id->toString()],
        ]);

        $this->artisan('petitions:restore-assignees')
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('Would update 1 petition(s)')
            ->assertSuccessful();

        // assigned_to must NOT have changed (dry-run)
        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'assigned_to' => $originalUser->id,
        ]);
    }

    #[Test]
    public function testCommitUpdatesPetitions(): void
    {
        $originalUser = User::factory()->create();
        $correctUser = User::factory()->create();
        $petition = Petition::factory()->create(['assigned_to' => $originalUser->id]);

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::ASSIGNMENT_OCCURRENCE,
            'data' => ['current_assigned_user_id' => $correctUser->id->toString()],
        ]);

        $this->artisan('petitions:restore-assignees', ['--commit' => true])
            ->expectsOutputToContain('Successfully updated 1 petition(s)')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'assigned_to' => $correctUser->id,
        ]);
    }

    #[Test]
    public function testCommitSetsNullAssignment(): void
    {
        $originalUser = User::factory()->create();
        $petition = Petition::factory()->create(['assigned_to' => $originalUser->id]);

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::ASSIGNMENT_OCCURRENCE,
            'data' => ['current_assigned_user_id' => null],
        ]);

        $this->artisan('petitions:restore-assignees', ['--commit' => true])
            ->expectsOutputToContain('Successfully updated 1 petition(s)')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'assigned_to' => null,
        ]);
    }

    #[Test]
    public function testUsesLatestTimelineItem(): void
    {
        $oldUser = User::factory()->create();
        $newUser = User::factory()->create();
        $petition = Petition::factory()->create(['assigned_to' => $oldUser->id]);

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::ASSIGNMENT_OCCURRENCE,
            'data' => ['current_assigned_user_id' => $newUser->id->toString()],
            'created_at' => now(),
        ]);

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::ASSIGNMENT_OCCURRENCE,
            'data' => ['current_assigned_user_id' => $oldUser->id->toString()],
            'created_at' => now()->subHour(),
        ]);

        $this->artisan('petitions:restore-assignees', ['--commit' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'assigned_to' => $newUser->id,
        ]);
    }

    #[Test]
    public function testShowsTableWithCaseNumberAndAssignees(): void
    {
        $originalUser = User::factory()->create();
        $correctUser = User::factory()->create();
        $petition = Petition::factory()->create(['assigned_to' => $originalUser->id]);

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::ASSIGNMENT_OCCURRENCE,
            'data' => ['current_assigned_user_id' => $correctUser->id->toString()],
        ]);

        $this->artisan('petitions:restore-assignees')
            ->expectsOutputToContain('Zaaknummer')
            ->expectsOutputToContain($petition->number)
            ->assertSuccessful();
    }

    #[Test]
    public function testSkipsUpdateWhenNewUserNoLongerExists(): void
    {
        $originalUser = User::factory()->create();
        $deletedUserId = Uuid::uuid7()->toString();
        $petition = Petition::factory()->create(['assigned_to' => $originalUser->id]);

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::ASSIGNMENT_OCCURRENCE,
            'data' => ['current_assigned_user_id' => $deletedUserId],
        ]);

        $this->artisan('petitions:restore-assignees')
            ->expectsOutputToContain('Would update 0 petition(s)')
            ->assertSuccessful();

        // assigned_to must remain unchanged
        $this->assertDatabaseHas('petitions', [
            'id' => $petition->id,
            'assigned_to' => $originalUser->id,
        ]);
    }

    #[Test]
    public function testCurrentAssigneeShownAsGeenWhenNotAssigned(): void
    {
        $correctUser = User::factory()->create();
        $petition = Petition::factory()->create(['assigned_to' => null]);

        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::ASSIGNMENT_OCCURRENCE,
            'data' => ['current_assigned_user_id' => $correctUser->id->toString()],
        ]);

        $this->artisan('petitions:restore-assignees')
            ->expectsOutputToContain('Would update 1 petition(s)')
            ->assertSuccessful();
    }
}
