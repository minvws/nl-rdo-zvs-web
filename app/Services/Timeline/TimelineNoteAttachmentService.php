<?php

declare(strict_types=1);

namespace App\Services\Timeline;

use App\Enums\TimelineType;
use App\Models\Attachment;
use App\Models\TimelineItem;
use Illuminate\Support\Collection;

final readonly class TimelineNoteAttachmentService
{
    /**
     * The files attached to the notes among the timeline items, in the order of the notes.
     *
     * @param Collection<int, TimelineItem> $timelineItems
     *
     * @return Collection<int, array{attachment: Attachment, note: TimelineItem}>
     */
    public function getNoteAttachments(Collection $timelineItems): Collection
    {
        $notes = $timelineItems->filter(
            static fn(TimelineItem $item): bool => $item->type === TimelineType::NOTE,
        );

        $attachmentIdsPerNote = $notes->mapWithKeys(
            static fn(TimelineItem $note): array => [$note->internal_id => $note->attachmentIds()],
        );

        $attachments = Attachment::query()
            ->whereIn('id', $attachmentIdsPerNote->flatten())
            ->get()
            ->keyBy(static fn(Attachment $attachment): string => $attachment->id->toString());

        return $notes->flatMap(
            static fn(TimelineItem $note): Collection => $attachments
                ->only($attachmentIdsPerNote->get($note->internal_id))
                ->map(static fn(Attachment $attachment): array => ['attachment' => $attachment, 'note' => $note]),
        )->values();
    }
}
