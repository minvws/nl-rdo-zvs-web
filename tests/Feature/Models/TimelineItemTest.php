<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\TimelineType;
use App\Models\TimelineItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Feature\FeatureTestCase;
use Webmozart\Assert\InvalidArgumentException;

use function fake;

class TimelineItemTest extends FeatureTestCase
{
    #[Test]
    public function testAttachmentIdsReturnsTheIdsOfTheNote(): void
    {
        $attachmentIds = [fake()->uuid(), fake()->uuid()];

        $note = TimelineItem::factory()->create([
            'type' => TimelineType::NOTE,
            'data' => ['comment' => 'foo', 'attachmentIds' => $attachmentIds],
        ]);

        $this->assertSame($attachmentIds, $note->refresh()->attachmentIds());
    }

    #[Test]
    public function testAttachmentIdsIsEmptyForANoteWithoutFiles(): void
    {
        $note = TimelineItem::factory()->create([
            'type' => TimelineType::NOTE,
            'data' => ['comment' => 'foo', 'attachmentIds' => []],
        ]);

        $this->assertSame([], $note->refresh()->attachmentIds());
    }

    #[Test]
    public function testAttachmentIdsIsEmptyWhenTheDataHasNoAttachmentIds(): void
    {
        $timelineItem = TimelineItem::factory()->create([
            'type' => TimelineType::NOTE_DELETED,
            'data' => ['reason' => 'foo'],
        ]);

        $this->assertSame([], $timelineItem->refresh()->attachmentIds());
    }

    #[Test]
    #[TestWith(['foo'])]
    #[TestWith([['foo' => 'bar']])]
    #[TestWith([[1, 2]])]
    public function testAttachmentIdsRejectsAnythingButAListOfStrings(mixed $attachmentIds): void
    {
        $note = TimelineItem::factory()->create([
            'type' => TimelineType::NOTE,
            'data' => ['attachmentIds' => $attachmentIds],
        ]);

        $this->expectException(InvalidArgumentException::class);

        $note->refresh()->attachmentIds();
    }
}
