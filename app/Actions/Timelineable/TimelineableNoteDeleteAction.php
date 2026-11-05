<?php

declare(strict_types=1);

namespace App\Actions\Timelineable;

use App\Enums\TimelineType;
use App\Models\Attachment;
use App\Models\Contracts\TimelineableInterface;
use App\Models\TimelineItem;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Webmozart\Assert\Assert;

final readonly class TimelineableNoteDeleteAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    public function execute(
        TimelineableInterface $timelineable,
        TimelineItem $note,
        User $user,
        ?string $reason,
    ): void {
        Assert::same($note->type, TimelineType::NOTE);

        $attachmentIds = $note->data['attachmentIds'] ?? [];
        Assert::isArray($attachmentIds);

        $attachments = Attachment::query()->whereIn('id', $attachmentIds)->get();

        $this->databaseManager->transaction(static function () use ($timelineable, $note, $user, $reason, $attachments): void {
            $timelineable->timelineItems()->create([
                'type' => TimelineType::NOTE_DELETED,
                'user_id' => $user->id,
                'data' => [
                    'reason' => $reason,
                    'attachment_names' => $attachments->pluck('name')->all(),
                ],
            ]);

            $attachments->each(static fn(Attachment $attachment): ?bool => $attachment->delete());
            $note->delete();
        });
    }
}
