<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Decision;

use App\Actions\Decision\DecisionUnarchiveAction;
use App\Enums\TimelineType;
use App\Models\Decision;
use App\Models\Department;
use App\Models\TimelineItem;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DecisionUnarchiveActionTest extends FeatureTestCase
{
    #[Test]
    public function testUnarchiveDecision(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create([
            'archived_at' => Carbon::now()->subDay(),
        ]);

        $this->assertNotNull($decision->archived_at);

        $action = $this->app->make(DecisionUnarchiveAction::class);

        $action->execute($decision, $user);

        $decision->refresh();

        $this->assertNull($decision->archived_at);

        $this->assertDatabaseHas(TimelineItem::class, [
            'timelineable_id' => $decision->id,
            'timelineable_type' => 'decision',
            'type' => TimelineType::DECISION_UNARCHIVED,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function testUnarchiveAlreadyUnarchivedDecisionDoesNothing(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $action = $this->app->make(DecisionUnarchiveAction::class);

        $action->execute($decision, $user);

        $decision->refresh();

        $this->assertNull($decision->archived_at);

        // Should not create a timeline item for already unarchived decision
        $this->assertDatabaseMissing(TimelineItem::class, [
            'timelineable_id' => $decision->id,
            'timelineable_type' => 'decision',
            'type' => TimelineType::DECISION_UNARCHIVED,
            'user_id' => $user->id,
        ]);
    }
}
