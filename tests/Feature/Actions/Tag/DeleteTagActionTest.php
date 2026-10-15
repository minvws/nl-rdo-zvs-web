<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Tag;

use App\Actions\Tag\DeleteTagAction;
use App\Models\Department;
use App\Models\Petition;
use App\Models\Tag;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

class DeleteTagActionTest extends FeatureTestCase
{
    public function testDeletesUnusedTag(): void
    {
        $tag = Tag::factory()->create();

        $this->app->make(DeleteTagAction::class)->execute($tag);

        $this->assertModelMissing($tag);
    }

    public function testDetachesPetitionsAndDeletesTag(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();
        $petitions = Petition::factory()->recycle($department)->count(2)->create();
        $tag->petitions()->attach($petitions);

        $this->app->make(DeleteTagAction::class)->execute($tag);

        $this->assertModelMissing($tag);
        $this->assertDatabaseMissing('petition_tag', ['tag_id' => $tag->id]);
        foreach ($petitions as $petition) {
            $this->assertModelExists($petition);
            $this->assertCount(0, $petition->tags()->get());
        }
    }

    public function testKeepsOtherTagsAttached(): void
    {
        $department = Department::factory()->create();
        $tagToDelete = Tag::factory()->recycle($department)->create();
        $tagToKeep = Tag::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petition->tags()->attach([$tagToDelete->id, $tagToKeep->id]);

        $this->app->make(DeleteTagAction::class)->execute($tagToDelete);

        $remaining = $petition->tags()->get();

        $this->assertCount(1, $remaining);
        $this->assertTrue($remaining->contains('id', $tagToKeep->id));
    }

    public function testRestoresLinksWhenDeletingFails(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petition->tags()->attach($tag);

        Tag::deleting(static fn (): never => throw new RuntimeException('Deleting failed'));

        try {
            $this->app->make(DeleteTagAction::class)->execute($tag);
            $this->fail('Expected the delete to fail.');
        } catch (RuntimeException $runtimeException) {
            $this->assertSame('Deleting failed', $runtimeException->getMessage());
        }

        $this->assertModelExists($tag);
        $this->assertDatabaseHas('petition_tag', ['petition_id' => $petition->id, 'tag_id' => $tag->id]);
    }
}
