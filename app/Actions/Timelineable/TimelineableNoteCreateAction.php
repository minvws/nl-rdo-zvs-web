<?php

declare(strict_types=1);

namespace App\Actions\Timelineable;

use App\Enums\TimelineType;
use App\Models\Attachment;
use App\Models\Contracts\TimelineableInterface;
use App\Models\User;
use App\Repositories\RepositoryTransactionInterface;
use Illuminate\Container\Attributes\Config;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Throwable;
use Webmozart\Assert\Assert;

use function array_key_exists;

readonly class TimelineableNoteCreateAction
{
    public function __construct(
        private RepositoryTransactionInterface $repositoryTransaction,
        private FilesystemManager $filesystemManager,
        #[Config('filesystems.attachments')]
        private string $attachmentsDisk,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    public function execute(TimelineableInterface $timelineable, User $user, array $attributes): void
    {
        $attachments = new Collection();

        if (array_key_exists('attachments', $attributes)) {
            Assert::isArray($attributes['attachments']);

            foreach ($attributes['attachments'] as $uploadedFile) {
                Assert::isInstanceOf($uploadedFile, UploadedFile::class);

                $path = $this->filesystemManager
                    ->disk($this->attachmentsDisk)
                    ->put('attachments', $uploadedFile);

                $attachments->push(Attachment::query()->create([
                    'disk' => $this->attachmentsDisk,
                    'path' => $path,
                    'name' => $uploadedFile->getClientOriginalName(),
                ]));
            }
        }

        $attachmentIds = $attachments->pluck('id')->map->toString();

        $timelineData = [
            'user_id' => $user->id,
            'type' => TimelineType::NOTE,
            'data' => [
                'comment' => $attributes['comment'],
                'attachmentIds' => $attachmentIds,
            ],
        ];

        $this->repositoryTransaction->transaction(static function () use ($attachments, $timelineable, $timelineData): void {
            $attachments->each(static function (Attachment $attachment): void {
                $attachment->save();
            });

            $timelineable->timelineItems()->create(
                $timelineData,
            );
        });
    }
}
