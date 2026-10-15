<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Collections\TagCollection;
use App\Enums\TagColor;
use App\Models\Department;
use App\Models\Petition;
use App\Models\Tag;
use App\QueryBuilders\TagQueryBuilder;
use Tests\Feature\FeatureTestCase;

class TagTest extends FeatureTestCase
{
    public function testHasFactory(): void
    {
        $tag = Tag::factory()->create();

        $this->assertNotNull($tag->id);
        $this->assertNotNull($tag->name);
        $this->assertNotNull($tag->department_id);
        $this->assertInstanceOf(TagColor::class, $tag->color);
        $this->assertTrue($tag->active);
    }

    public function testDepartmentRelationship(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();

        $this->assertEquals($department->id, $tag->department->id);
        $this->assertEquals(1, $department->tags->count());
    }

    public function testCastsActiveAsBooleanAndColorAsEnum(): void
    {
        $tag = Tag::factory()->create([
            'active' => false,
            'color' => TagColor::RED,
        ]);

        $this->assertIsBool($tag->active);
        $this->assertFalse($tag->active);
        $this->assertSame(TagColor::RED, $tag->color);
    }

    public function testDescriptionIsOptional(): void
    {
        $tag = Tag::factory()->create(['description' => null]);

        $this->assertNull($tag->description);
    }

    public function testQueryReturnsTagQueryBuilder(): void
    {
        $this->assertInstanceOf(TagQueryBuilder::class, Tag::query());
    }

    public function testActiveScope(): void
    {
        $department = Department::factory()->create();
        $activeTag = Tag::factory()->recycle($department)->create(['active' => true]);
        $inactiveTag = Tag::factory()->recycle($department)->create(['active' => false]);

        $result = Tag::query()->forDepartment($department)->active()->get();

        $this->assertTrue($result->contains('id', $activeTag->id));
        $this->assertFalse($result->contains('id', $inactiveTag->id));
    }

    public function testForDepartmentScopeExcludesOtherDepartments(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();

        $ownTag = Tag::factory()->recycle($department)->create();
        $otherTag = Tag::factory()->recycle($otherDepartment)->create();

        $result = Tag::query()->forDepartment($department)->get();

        $this->assertTrue($result->contains('id', $ownTag->id));
        $this->assertFalse($result->contains('id', $otherTag->id));
    }

    public function testSelectableForPetitionScope(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $otherPetition = Petition::factory()->recycle($department)->create();

        $activeTag = Tag::factory()->recycle($department)->create();
        $attachedInactiveTag = Tag::factory()->recycle($department)->inactive()->create();
        $inactiveTagOnOtherPetition = Tag::factory()->recycle($department)->inactive()->create();
        $foreignTag = Tag::factory()->recycle(Department::factory()->create())->create();
        $attachedForeignTag = Tag::factory()->recycle(Department::factory()->create())->create();

        $petition->tags()->attach([$attachedInactiveTag, $attachedForeignTag]);
        $otherPetition->tags()->attach($inactiveTagOnOtherPetition);

        $ids = Tag::query()
            ->selectableForPetition($petition)
            ->get()
            ->map(static fn(Tag $tag): string => $tag->id->toString())
            ->all();

        $this->assertEqualsCanonicalizing([$activeTag->id->toString(), $attachedInactiveTag->id->toString()], $ids);
        $this->assertNotContains($inactiveTagOnOtherPetition->id->toString(), $ids);
        $this->assertNotContains($foreignTag->id->toString(), $ids);
    }

    public function testIsOrderedByName(): void
    {
        $department = Department::factory()->create();
        Tag::factory()->recycle($department)->create(['name' => 'Zeldzaam']);
        Tag::factory()->recycle($department)->create(['name' => 'Aandacht']);

        $names = Tag::query()->forDepartment($department)->get()->pluck('name')->all();

        $this->assertSame(['Aandacht', 'Zeldzaam'], $names);
    }

    public function testCollectionToString(): void
    {
        $department = Department::factory()->create();
        Tag::factory()->recycle($department)->create(['name' => 'Aandacht']);
        Tag::factory()->recycle($department)->create(['name' => 'Veelklager']);

        $tags = Tag::query()->forDepartment($department)->get();

        $this->assertInstanceOf(TagCollection::class, $tags);
        $this->assertSame('Aandacht, Veelklager', $tags->toString());
    }

    public function testInactiveFactoryState(): void
    {
        $tag = Tag::factory()->inactive()->create();

        $this->assertFalse($tag->active);
    }

    public function testPetitionsRelationship(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->create();

        $petition->tags()->attach($tag);

        $this->assertTrue($petition->fresh()->tags->contains('id', $tag->id));
        $this->assertTrue($tag->fresh()->petitions->contains('id', $petition->id));
    }

    public function testPetitionsCountIsAvailable(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petition->tags()->attach($tag);

        $tagWithCount = Tag::query()->withCount('petitions')->whereKey($tag->id)->sole();

        $this->assertSame(1, $tagWithCount->petitions_count);
    }

    public function testDeletingPetitionRemovesItsTagLinks(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petition->tags()->attach($tag);

        $petition->delete();

        $this->assertDatabaseMissing('petition_tag', ['tag_id' => $tag->id->toString()]);
        $this->assertModelExists($tag);
    }
}
