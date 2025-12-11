<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Decision;

use App\Actions\Decision\DecisionArchiveAction;
use App\Enums\TimelineType;
use App\Models\Decision;
use App\Models\Department;
use App\Models\TimelineItem;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DecisionArchiveActionTest extends FeatureTestCase
{
    #[Test]
    public function testArchiveDecision(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $this->assertNull($decision->archived_at);

        $action = $this->app->make(DecisionArchiveAction::class);

        Carbon::setTestNow('2025-07-10 12:00:00');

        $action->execute($decision, $user);

        $decision->refresh();

        $this->assertNotNull($decision->archived_at);
        $this->assertEquals('2025-07-10 12:00:00', $decision->archived_at->format('Y-m-d H:i:s'));

        $this->assertDatabaseHas(TimelineItem::class, [
            'timelineable_id' => $decision->id,
            'timelineable_type' => 'decision',
            'type' => TimelineType::DECISION_ARCHIVED,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function testArchiveAlreadyArchivedDecisionDoesNothing(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create([
            'archived_at' => Carbon::now()->subDay(),
        ]);

        $originalArchivedAt = $decision->archived_at;

        $action = $this->app->make(DecisionArchiveAction::class);

        Carbon::setTestNow('2025-07-10 12:00:00');

        $action->execute($decision, $user);

        $decision->refresh();

        $this->assertNotEquals('2025-07-10 12:00:00', $decision->archived_at->format('Y-m-d H:i:s'));
        $this->assertEquals($originalArchivedAt->format('Y-m-d H:i:s'), $decision->archived_at->format('Y-m-d H:i:s'));
    }
}
