<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Throwable;
use Webmozart\Assert\Assert;

readonly class PetitionTagsUpdateAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * Saving without a change writes no timeline item. The timeline item keeps the tag names as they were at that
     * moment, so the history stays readable after a tag is renamed or deleted.
     *
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    public function execute(Petition $petition, User $user, array $attributes): void
    {
        $this->databaseManager->transaction(
            static function () use ($petition, $user, $attributes): void {
                $tagIds = $attributes['tag_ids'];
                Assert::isArray($tagIds);
                Assert::allString($tagIds);
                Assert::allUuid($tagIds);

                $changes = $petition->tags()->sync($tagIds);

                $tags = $petition->tags()->get();
                $petition->setRelation('tags', $tags);

                if ($changes['attached'] === [] && $changes['detached'] === []) {
                    return;
                }

                $petition->timelineItems()->create([
                    'user_id' => $user->id,
                    'type' => TimelineType::TAGS_CHANGED,
                    'data' => new ArrayObject([
                        'tag_ids' => $tags->map(static fn(Tag $tag): string => $tag->id->toString())->all(),
                        'tag_names' => $tags->map(static fn(Tag $tag): string => $tag->name)->all(),
                    ]),
                ]);
            },
        );
    }
}
