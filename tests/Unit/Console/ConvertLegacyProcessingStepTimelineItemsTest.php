<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Models\TimelineItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function now;

final class ConvertLegacyProcessingStepTimelineItemsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function handleConvertsLegacyAssignedToToFirstAssignee(): void
    {
        TimelineItem::create([
            'internal_id' => 1,
            'timelineable_id' => '019da551-0000-0000-0000-000000000001',
            'timelineable_type' => 'decision',
            'type' => 'processing_step_created',
            'user_id' => null,
            'data' => [
                'name' => 'Test Step',
                'assigned_to' => '019da551-0000-0000-0000-000000000002',
                'deadline_at' => '2026-04-20',
                'status' => 'draft',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('app:convert-legacy-processing-step-timeline-items')
            ->assertExitCode(0);

        $item = TimelineItem::query()->find(1);
        $data = $item->data;

        $this->assertArrayHasKey('first_assignee', $data);
        $this->assertSame('019da551-0000-0000-0000-000000000002', $data['first_assignee']);
        $this->assertArrayNotHasKey('assigned_to', $data);
    }

    #[Test]
    public function handleSkipsAlreadyConvertedItems(): void
    {
        TimelineItem::create([
            'internal_id' => 1,
            'timelineable_id' => '019da551-0000-0000-0000-000000000001',
            'timelineable_type' => 'decision',
            'type' => 'processing_step_created',
            'user_id' => null,
            'data' => [
                'name' => 'Test Step',
                'first_assignee' => '019da551-0000-0000-0000-000000000002',
                'deadline_at' => '2026-04-20',
                'status' => 'draft',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('app:convert-legacy-processing-step-timeline-items')
            ->assertExitCode(0);
    }

    #[Test]
    public function handleSkipsNonProcessingStepItems(): void
    {
        TimelineItem::create([
            'internal_id' => 1,
            'timelineable_id' => '019da551-0000-0000-0000-000000000001',
            'timelineable_type' => 'petition',
            'type' => 'timelineable_created',
            'user_id' => null,
            'data' => [
                'name' => 'Test Petition',
                'assigned_to' => '019da551-0000-0000-0000-000000000002',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('app:convert-legacy-processing-step-timeline-items')
            ->assertExitCode(0);

        $item = TimelineItem::query()->find(1);
        $data = $item->data;

        $this->assertArrayHasKey('assigned_to', $data);
        $this->assertArrayNotHasKey('first_assignee', $data);
    }

    #[Test]
    public function handleSkipsItemsWithNoAssignedTo(): void
    {
        TimelineItem::create([
            'internal_id' => 1,
            'timelineable_id' => '019da551-0000-0000-0000-000000000001',
            'timelineable_type' => 'decision',
            'type' => 'processing_step_created',
            'user_id' => null,
            'data' => [
                'name' => 'Test Step',
                'deadline_at' => '2026-04-20',
                'status' => 'draft',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('app:convert-legacy-processing-step-timeline-items')
            ->assertExitCode(0);
    }

    #[Test]
    public function handleConvertsMultipleTypes(): void
    {
        TimelineItem::create([
            'internal_id' => 1,
            'timelineable_id' => '019da551-0000-0000-0000-000000000001',
            'timelineable_type' => 'decision',
            'type' => 'processing_step_created',
            'user_id' => null,
            'data' => [
                'name' => 'Test Step 1',
                'assigned_to' => '019da551-0000-0000-0000-000000000002',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TimelineItem::create([
            'internal_id' => 2,
            'timelineable_id' => '019da551-0000-0000-0000-000000000001',
            'timelineable_type' => 'decision',
            'type' => 'processing_step_updated',
            'user_id' => null,
            'data' => [
                'name' => 'Test Step 2',
                'assigned_to' => '019da551-0000-0000-0000-000000000003',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TimelineItem::create([
            'internal_id' => 3,
            'timelineable_id' => '019da551-0000-0000-0000-000000000001',
            'timelineable_type' => 'decision',
            'type' => 'processing_step_deleted',
            'user_id' => null,
            'data' => [
                'name' => 'Test Step 3',
                'assigned_to' => '019da551-0000-0000-0000-000000000004',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('app:convert-legacy-processing-step-timeline-items')
            ->assertExitCode(0);

        $this->assertDatabaseCount('timeline_items', 3);
    }
}
