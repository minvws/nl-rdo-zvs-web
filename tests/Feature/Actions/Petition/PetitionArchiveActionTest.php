<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition;

use App\Actions\Petition\PetitionArchiveAction;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\TimelineItem;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PetitionArchiveActionTest extends FeatureTestCase
{
    #[Test]
    public function testArchivePetition(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $this->assertNull($petition->archived_at);

        $action = $this->app->make(PetitionArchiveAction::class);

        Carbon::setTestNow('2025-07-10 12:00:00');

        $action->execute($petition, $user);

        $petition->refresh();

        $this->assertNotNull($petition->archived_at);
        $this->assertEquals('2025-07-10 12:00:00', $petition->archived_at->format('Y-m-d H:i:s'));

        $this->assertDatabaseHas(TimelineItem::class, [
            'timelineable_id' => $petition->id,
            'timelineable_type' => 'petition',
            'type' => TimelineType::PETITION_ARCHIVED,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function testArchiveAlreadyArchivedPetition(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => Carbon::now()->subDay(),
        ]);

        $originalArchivedAt = $petition->archived_at;

        $action = $this->app->make(PetitionArchiveAction::class);

        Carbon::setTestNow('2025-07-10 12:00:00');

        $action->execute($petition, $user);

        $petition->refresh();

        $this->assertEquals('2025-07-10 12:00:00', $petition->archived_at->format('Y-m-d H:i:s'));
        $this->assertNotEquals($originalArchivedAt, $petition->archived_at);
    }
}
