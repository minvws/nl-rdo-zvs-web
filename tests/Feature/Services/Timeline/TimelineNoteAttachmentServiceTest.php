<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Timeline;

use App\Enums\TimelineType;
use App\Models\Attachment;
use App\Models\Decision;
use App\Models\Petition;
use App\Models\TimelineItem;
use App\Services\Timeline\TimelineNoteAttachmentService;
use Illuminate\Support\Collection;
use Tests\Feature\FeatureTestCase;

use function array_map;

class TimelineNoteAttachmentServiceTest extends FeatureTestCase
{
    public function testGetNoteAttachmentsReturnsTheFilesOfEachNoteNewestNoteFirst(): void
    {
        $petition = Petition::factory()->create();
        $olderAttachment = Attachment::factory()->create();
        $newerAttachments = Attachment::factory()->count(2)->create();

        $olderNote = $this->createNote($petition, [$olderAttachment], '2026-10-01 10:00:00');
        $newerNote = $this->createNote($petition, $newerAttachments->all(), '2026-10-02 10:00:00');

        $noteAttachments = $this->getNoteAttachments($petition->refresh()->timelineItems);

        $this->assertCount(3, $noteAttachments);
        $this->assertSame(
            [(string) $newerNote->internal_id, (string) $newerNote->internal_id, (string) $olderNote->internal_id],
            $noteAttachments->map(
                static fn(array $noteAttachment): string => (string) $noteAttachment['note']->internal_id,
            )->all(),
        );
        $this->assertEqualsCanonicalizing(
            $newerAttachments->map(static fn(Attachment $attachment): string => $attachment->id->toString())->all(),
            $noteAttachments->take(2)->map(
                static fn(array $noteAttachment): string => $noteAttachment['attachment']->id->toString(),
            )->all(),
        );
        $this->assertSame($olderAttachment->id->toString(), $noteAttachments->last()['attachment']->id->toString());
    }

    public function testGetNoteAttachmentsIgnoresNotesWithoutFiles(): void
    {
        $petition = Petition::factory()->create();
        $this->createNote($petition, [], '2026-10-01 10:00:00');
        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::NOTE,
            'data' => ['comment' => $this->faker->sentence()],
        ]);

        $noteAttachments = $this->getNoteAttachments($petition->refresh()->timelineItems);

        $this->assertTrue($noteAttachments->isEmpty());
    }

    public function testGetNoteAttachmentsSkipsDeletedFiles(): void
    {
        $petition = Petition::factory()->create();
        $deletedAttachment = Attachment::factory()->create();
        $attachment = Attachment::factory()->create();
        $this->createNote($petition, [$deletedAttachment, $attachment], '2026-10-01 10:00:00');
        $deletedAttachment->delete();

        $noteAttachments = $this->getNoteAttachments($petition->refresh()->timelineItems);

        $this->assertCount(1, $noteAttachments);
        $this->assertSame($attachment->id->toString(), $noteAttachments->first()['attachment']->id->toString());
    }

    public function testGetNoteAttachmentsIgnoresOtherTimelineItems(): void
    {
        $petition = Petition::factory()->create();
        TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::NOTE_DELETED,
            'data' => ['attachmentIds' => [Attachment::factory()->create()->id->toString()]],
        ]);

        $noteAttachments = $this->getNoteAttachments($petition->refresh()->timelineItems);

        $this->assertTrue($noteAttachments->isEmpty());
    }

    public function testGetNoteAttachmentsReturnsTheFilesOfDecisionNotes(): void
    {
        $decision = Decision::factory()->create();
        $attachment = Attachment::factory()->create();
        TimelineItem::factory()->recycle($decision)->create([
            'type' => TimelineType::NOTE,
            'data' => [
                'comment' => $this->faker->sentence(),
                'attachmentIds' => [$attachment->id->toString()],
            ],
        ]);

        $noteAttachments = $this->getNoteAttachments($decision->refresh()->timelineItems);

        $this->assertCount(1, $noteAttachments);
        $this->assertSame($attachment->id->toString(), $noteAttachments->first()['attachment']->id->toString());
    }

    /**
     * @param Collection<int, TimelineItem> $timelineItems
     *
     * @return Collection<int, array{attachment: Attachment, note: TimelineItem}>
     */
    private function getNoteAttachments(Collection $timelineItems): Collection
    {
        return $this->app->make(TimelineNoteAttachmentService::class)->getNoteAttachments($timelineItems);
    }

    /**
     * @param array<int, Attachment> $attachments
     */
    private function createNote(Petition $petition, array $attachments, string $createdAt): TimelineItem
    {
        return TimelineItem::factory()->recycle($petition)->create([
            'type' => TimelineType::NOTE,
            'created_at' => $createdAt,
            'data' => [
                'comment' => $this->faker->sentence(),
                'attachmentIds' => array_map(
                    static fn(Attachment $attachment): string => $attachment->id->toString(),
                    $attachments,
                ),
            ],
        ]);
    }
}
