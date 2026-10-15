<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition;

use App\Actions\Petition\PetitionTagsUpdateAction;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\Tag;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class PetitionTagsUpdateActionTest extends FeatureTestCase
{
    public function testAttachesTagsAndWritesTimelineItem(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $tag = Tag::factory()->recycle($department)->create(['name' => 'Veelklager']);
        $user = User::factory()->create();

        $this->app->make(PetitionTagsUpdateAction::class)
            ->execute($petition, $user, ['tag_ids' => [$tag->id->toString()]]);

        $this->assertSame([$tag->id->toString()], $this->tagIdsOf($petition->fresh()));
        $this->assertSame(['Veelklager'], $petition->tags->pluck('name')->all());

        $timelineItem = $petition->timelineItems()->where('type', TimelineType::TAGS_CHANGED)->sole();

        $this->assertSame([$tag->id->toString()], (array) $timelineItem->data['tag_ids']);
        $this->assertSame(['Veelklager'], (array) $timelineItem->data['tag_names']);
        $this->assertEquals($user->id, $timelineItem->user_id);
    }

    public function testDetachesTagsThatAreNoLongerSelected(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $tagToKeep = Tag::factory()->recycle($department)->create(['name' => 'Blijft']);
        $tagToRemove = Tag::factory()->recycle($department)->create(['name' => 'Gaat weg']);
        $petition->tags()->attach([$tagToKeep, $tagToRemove]);
        $user = User::factory()->create();

        $this->app->make(PetitionTagsUpdateAction::class)
            ->execute($petition, $user, ['tag_ids' => [$tagToKeep->id->toString()]]);

        $this->assertSame([$tagToKeep->id->toString()], $this->tagIdsOf($petition->fresh()));

        $timelineItem = $petition->timelineItems()->where('type', TimelineType::TAGS_CHANGED)->sole();
        $this->assertSame(['Blijft'], (array) $timelineItem->data['tag_names']);
    }

    public function testClearsAllTags(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $tag = Tag::factory()->recycle($department)->create();
        $petition->tags()->attach($tag);
        $user = User::factory()->create();

        $this->app->make(PetitionTagsUpdateAction::class)
            ->execute($petition, $user, ['tag_ids' => []]);

        $this->assertSame([], $this->tagIdsOf($petition->fresh()));

        $timelineItem = $petition->timelineItems()->where('type', TimelineType::TAGS_CHANGED)->sole();
        $this->assertSame([], (array) $timelineItem->data['tag_ids']);
        $this->assertSame([], (array) $timelineItem->data['tag_names']);
    }

    public function testWritesNoTimelineItemWhenNothingChanged(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $tag = Tag::factory()->recycle($department)->create();
        $petition->tags()->attach($tag);
        $user = User::factory()->create();

        $this->app->make(PetitionTagsUpdateAction::class)
            ->execute($petition, $user, ['tag_ids' => [$tag->id->toString()]]);

        $this->assertSame([$tag->id->toString()], $this->tagIdsOf($petition->fresh()));
        $this->assertFalse($petition->timelineItems()->where('type', TimelineType::TAGS_CHANGED)->exists());
    }

    public function testRefreshesTheLoadedTagsOfThePetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $tag = Tag::factory()->recycle($department)->create();
        $petition->load('tags');
        $user = User::factory()->create();

        $this->app->make(PetitionTagsUpdateAction::class)
            ->execute($petition, $user, ['tag_ids' => [$tag->id->toString()]]);

        $this->assertSame([$tag->id->toString()], $this->tagIdsOf($petition));
    }

    /**
     * @return array<int, string>
     */
    private function tagIdsOf(Petition $petition): array
    {
        return $petition->tags->map(static fn(Tag $tag): string => $tag->id->toString())->all();
    }
}
