<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

use function __;
use function route;
use function sprintf;

class PetitionTagControllerTest extends FeatureTestCase
{
    private Department $department;
    private Petition $petition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
        $this->petition = Petition::factory()->recycle($this->department)->create();
    }

    public function testShowListsTheTagsOfThePetition(): void
    {
        $tag = Tag::factory()->recycle($this->department)->create(['name' => 'Veelklager']);
        $this->petition->tags()->attach($tag);

        $this->beUser($this->readUser())
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_SHOW, $this->routeParameters())
            ->assertOk()
            ->assertViewIs('petition.tags.show')
            ->assertSee('Veelklager')
            ->assertSee('tag--color-' . $tag->color->value, false);
    }

    public function testShowListsAllTagsWithoutACounter(): void
    {
        $tags = Tag::factory()->recycle($this->department)->count(5)->create();
        $this->petition->tags()->attach($tags);

        $response = $this->beUser($this->readUser())
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_SHOW, $this->routeParameters())
            ->assertOk();

        foreach ($tags as $tag) {
            $response->assertSee(sprintf('<span class="tag tag--color-%s">%s</span>', $tag->color->value, $tag->name), false);
        }
        $response->assertDontSee('<span class="tag" title=', false);
    }

    public function testShowWithoutTags(): void
    {
        $this->beUser($this->readUser())
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_SHOW, $this->routeParameters())
            ->assertOk()
            ->assertSee(__('tag.none'));
    }

    public function testShowHasEditLinkForWriteUser(): void
    {
        $this->beUser($this->writeUser(), true, $this->department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_SHOW, $this->routeParameters())
            ->assertOk()
            ->assertSee($this->editUrl(), false);
    }

    public function testShowHasNoEditLinkForReadUser(): void
    {
        $this->beUser($this->readUser())
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_SHOW, $this->routeParameters())
            ->assertOk()
            ->assertDontSee($this->editUrl(), false);
    }

    public function testShowHasNoEditLinkForArchivedPetition(): void
    {
        $this->petition->update(['archived_at' => Carbon::now()]);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($this->department, Permission::PETITION_READ, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $this->department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_SHOW, $this->routeParameters())
            ->assertOk()
            ->assertDontSee($this->editUrl(), false);
    }

    public function testShowRequiresPermission(): void
    {
        $this->beUser(User::factory()->fullyVerified()->create())
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_SHOW, $this->routeParameters())
            ->assertForbidden();
    }

    public function testShowWhenPetitionNotFound(): void
    {
        $this->beUser($this->readUser())
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_SHOW, [
                'department' => $this->department,
                'petition' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    public function testEditOffersActiveTagsOfOwnDepartmentOnly(): void
    {
        $activeTag = Tag::factory()->recycle($this->department)->create(['name' => 'Actieve tag']);
        $inactiveTag = Tag::factory()->recycle($this->department)->inactive()->create(['name' => 'Inactieve tag']);
        $foreignTag = Tag::factory()->recycle(Department::factory()->create())->create(['name' => 'Andere afdeling']);

        $response = $this->beUser($this->writeUser(), true, $this->department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_EDIT, $this->routeParameters())
            ->assertOk()
            ->assertViewIs('form')
            ->assertSee($activeTag->name)
            ->assertDontSee($inactiveTag->name)
            ->assertDontSee($foreignTag->name);

        $this->assertSame([$activeTag->id->toString()], $this->idsOf($response->viewData('tags')));
    }

    public function testEditChecksAttachedTagsAndKeepsAnAttachedInactiveTag(): void
    {
        $attachedTag = Tag::factory()->recycle($this->department)->create(['name' => 'Gekoppeld']);
        $inactiveAttachedTag = Tag::factory()->recycle($this->department)->inactive()->create(['name' => 'Uitgefaseerd']);
        $otherTag = Tag::factory()->recycle($this->department)->create(['name' => 'Niet gekoppeld']);
        $this->petition->tags()->attach([$attachedTag, $inactiveAttachedTag]);

        $response = $this->beUser($this->writeUser(), true, $this->department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_EDIT, $this->routeParameters())
            ->assertOk()
            ->assertSee('Uitgefaseerd')
            ->assertSee(__('tag.inactive_hint'));

        $this->assertEqualsCanonicalizing(
            [$attachedTag->id->toString(), $inactiveAttachedTag->id->toString(), $otherTag->id->toString()],
            $this->idsOf($response->viewData('tags')),
        );

        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertMatchesRegularExpression($this->checkedCheckboxPattern($attachedTag), $content);
        $this->assertMatchesRegularExpression($this->checkedCheckboxPattern($inactiveAttachedTag), $content);
        $this->assertDoesNotMatchRegularExpression($this->checkedCheckboxPattern($otherTag), $content);
    }

    public function testEditShowsTheDescriptionOfEachTagAsHint(): void
    {
        $describedTag = Tag::factory()->recycle($this->department)->create(['description' => 'Voor indieners die steeds terugkomen']);
        $describedInactiveTag = Tag::factory()->recycle($this->department)->inactive()->create(['description' => 'Niet meer gebruiken']);
        $plainTag = Tag::factory()->recycle($this->department)->create(['description' => null]);
        $this->petition->tags()->attach($describedInactiveTag);

        $response = $this->beUser($this->writeUser(), true, $this->department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_EDIT, $this->routeParameters())
            ->assertOk()
            ->assertSee('Voor indieners die steeds terugkomen')
            ->assertSee('Niet meer gebruiken')
            ->assertSee(sprintf('aria-describedby="tag-%s-description"', $describedTag->id), false)
            ->assertSee(sprintf('aria-describedby="tag-%1$s-description tag-%1$s-hint"', $describedInactiveTag->id), false)
            ->assertDontSee(sprintf('tag-%s-description', $plainTag->id), false);

        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertDoesNotMatchRegularExpression(sprintf('/id="tag-%s"[^>]*aria-describedby/s', $plainTag->id), $content);
        $this->assertMatchesRegularExpression(
            sprintf(
                '/<input\s+id="tag-%1$s".*?\/>\s*<label[^>]*for="tag-%1$s".*?<\/div>\s*<p class="nota-bene">.*?id="tag-%1$s-description".*?id="tag-%1$s-hint".*?<\/p>/s',
                $describedInactiveTag->id,
            ),
            $content,
        );
    }

    public function testEditWithoutAnyTags(): void
    {
        $this->beUser($this->writeUser(), true, $this->department)
            ->getByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_TAGS_EDIT, $this->routeParameters())
            ->assertOk()
            ->assertSee(__('tag.no_records'));
    }

    public function testEditRequiresWritePermission(): void
    {
        $this->beUser($this->readUser())
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_EDIT, $this->routeParameters())
            ->assertForbidden();
    }

    public function testEditIsForbiddenForArchivedPetition(): void
    {
        $this->petition->update(['archived_at' => Carbon::now()]);

        $this->beUser($this->writeUser(), true, $this->department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_EDIT, $this->routeParameters())
            ->assertForbidden();
    }

    public function testUpdateAttachesAndDetachesTags(): void
    {
        $tagToKeep = Tag::factory()->recycle($this->department)->create();
        $tagToRemove = Tag::factory()->recycle($this->department)->create();
        $tagToAdd = Tag::factory()->recycle($this->department)->create();
        $this->petition->tags()->attach([$tagToKeep, $tagToRemove]);

        $this->beUser($this->writeUser(), true, $this->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_UPDATE, $this->routeParameters(), [
                'tag_ids' => [$tagToKeep->id->toString(), $tagToAdd->id->toString()],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ])
            ->assertSessionHas('message.success', __('general.saved'));

        $this->assertEqualsCanonicalizing(
            [$tagToKeep->id->toString(), $tagToAdd->id->toString()],
            $this->attachedTagIds(),
        );
        $this->assertSame(1, $this->tagsChangedTimelineItemCount());
    }

    public function testUpdateToNoTags(): void
    {
        $this->petition->tags()->attach(Tag::factory()->recycle($this->department)->create());

        $this->beUser($this->writeUser(), true, $this->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_UPDATE, $this->routeParameters(), [])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]);

        $this->assertSame([], $this->attachedTagIds());
        $this->assertSame(1, $this->tagsChangedTimelineItemCount());
    }

    public function testUpdateWithoutChangesWritesNoTimelineItem(): void
    {
        $tag = Tag::factory()->recycle($this->department)->create();
        $this->petition->tags()->attach($tag);

        $this->beUser($this->writeUser(), true, $this->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_UPDATE, $this->routeParameters(), [
                'tag_ids' => [$tag->id->toString()],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame([$tag->id->toString()], $this->attachedTagIds());
        $this->assertSame(0, $this->tagsChangedTimelineItemCount());
    }

    public function testUpdateIgnoresEmptyValues(): void
    {
        $tag = Tag::factory()->recycle($this->department)->create();

        $this->beUser($this->writeUser(), true, $this->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_UPDATE, $this->routeParameters(), [
                'tag_ids' => ['', $tag->id->toString()],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([$tag->id->toString()], $this->attachedTagIds());
    }

    public function testUpdateKeepsAnAttachedInactiveTag(): void
    {
        $inactiveTag = Tag::factory()->recycle($this->department)->inactive()->create();
        $activeTag = Tag::factory()->recycle($this->department)->create();
        $this->petition->tags()->attach($inactiveTag);

        $this->beUser($this->writeUser(), true, $this->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_UPDATE, $this->routeParameters(), [
                'tag_ids' => [$inactiveTag->id->toString(), $activeTag->id->toString()],
            ])
            ->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(
            [$inactiveTag->id->toString(), $activeTag->id->toString()],
            $this->attachedTagIds(),
        );
    }

    public function testUpdateRemovesAnAttachedInactiveTag(): void
    {
        $inactiveTag = Tag::factory()->recycle($this->department)->inactive()->create();
        $this->petition->tags()->attach($inactiveTag);

        $this->beUser($this->writeUser(), true, $this->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_UPDATE, $this->routeParameters(), [
                'tag_ids' => [],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([], $this->attachedTagIds());
    }

    public function testUpdateRejectsAddingAnInactiveTag(): void
    {
        $inactiveTag = Tag::factory()->recycle($this->department)->inactive()->create();

        $this->assertUpdateRejected(['tag_ids' => [$inactiveTag->id->toString()]], 'tag_ids.0');
    }

    public function testUpdateRejectsTagFromAnotherDepartment(): void
    {
        $foreignTag = Tag::factory()->recycle(Department::factory()->create())->create();

        $this->assertUpdateRejected(['tag_ids' => [$foreignTag->id->toString()]], 'tag_ids.0');
    }

    public function testUpdateRejectsTheWholeRequestWhenOneTagIsInvalid(): void
    {
        $ownTag = Tag::factory()->recycle($this->department)->create();
        $foreignTag = Tag::factory()->recycle(Department::factory()->create())->create();

        $this->assertUpdateRejected(
            ['tag_ids' => [$ownTag->id->toString(), $foreignTag->id->toString()]],
            'tag_ids.1',
        );
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidInputProvider(): array
    {
        return [
            'non-existing uuid' => [['tag_ids' => ['6f1c1d4e-5b8a-4c35-9f65-2b1c0b0f0a11']], 'tag_ids.0'],
            'not a uuid' => [['tag_ids' => ['geen-uuid']], 'tag_ids.0'],
            'nested array' => [['tag_ids' => [['6f1c1d4e-5b8a-4c35-9f65-2b1c0b0f0a11']]], 'tag_ids.0'],
            'not an array' => [['tag_ids' => 'geen-lijst'], 'tag_ids'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('invalidInputProvider')]
    public function testUpdateRejectsInvalidInput(array $data, string $errorKey): void
    {
        $this->assertUpdateRejected($data, $errorKey);
    }

    public function testUpdateAsHtmxReturnsTheShowBlock(): void
    {
        $tag = Tag::factory()->recycle($this->department)->create(['name' => 'Veelklager']);

        $this->beUser($this->writeUser(), true, $this->department)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_TAGS_UPDATE, $this->routeParameters(), [
                'tag_ids' => [$tag->id->toString()],
            ])
            ->assertOk()
            ->assertViewIs('petition.tags.show')
            ->assertSee('Veelklager');
    }

    public function testUpdateRequiresWritePermission(): void
    {
        $tag = Tag::factory()->recycle($this->department)->create();

        $this->beUser($this->readUser())
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_UPDATE, $this->routeParameters(), [
                'tag_ids' => [$tag->id->toString()],
            ])
            ->assertForbidden();

        $this->assertSame([], $this->attachedTagIds());
    }

    public function testUpdateIsForbiddenWithoutPermission(): void
    {
        $tag = Tag::factory()->recycle($this->department)->create();

        $this->beUser(User::factory()->fullyVerified()->create())
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_UPDATE, $this->routeParameters(), [
                'tag_ids' => [$tag->id->toString()],
            ])
            ->assertForbidden();

        $this->assertSame([], $this->attachedTagIds());
    }

    public function testUpdateIsForbiddenForArchivedPetition(): void
    {
        $tag = Tag::factory()->recycle($this->department)->create();
        $this->petition->update(['archived_at' => Carbon::now()]);

        $this->beUser($this->writeUser(), true, $this->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_UPDATE, $this->routeParameters(), [
                'tag_ids' => [$tag->id->toString()],
            ])
            ->assertForbidden();

        $this->assertSame([], $this->attachedTagIds());
    }

    public function testUpdateWhenPetitionNotFound(): void
    {
        $this->beUser($this->writeUser(), true, $this->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_UPDATE, [
                'department' => $this->department,
                'petition' => $this->faker->uuid(),
            ], [
                'tag_ids' => [],
            ])
            ->assertNotFound();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertUpdateRejected(array $data, string $errorKey): void
    {
        $this->beUser($this->writeUser(), true, $this->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TAGS_UPDATE, $this->routeParameters(), $data)
            ->assertSessionHasErrors($errorKey);

        $this->assertSame([], $this->attachedTagIds());
        $this->assertSame(0, $this->tagsChangedTimelineItemCount());
    }

    /**
     * @return array<string, mixed>
     */
    private function routeParameters(): array
    {
        return [
            'department' => $this->department,
            'petition' => $this->petition,
        ];
    }

    private function editUrl(): string
    {
        return route(RouteName::DEPARTMENTS_PETITIONS_TAGS_EDIT, [
            'department' => $this->department->slug,
            'petition' => $this->petition,
        ]);
    }

    private function readUser(): User
    {
        return User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
    }

    private function writeUser(): User
    {
        return User::factory()
            ->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();
    }

    /**
     * @return array<int, string>
     */
    private function attachedTagIds(): array
    {
        return $this->idsOf($this->petition->tags()->get());
    }

    /**
     * @param iterable<Tag> $tags
     *
     * @return array<int, string>
     */
    private function idsOf(iterable $tags): array
    {
        $ids = [];
        foreach ($tags as $tag) {
            $ids[] = $tag->id->toString();
        }

        return $ids;
    }

    private function tagsChangedTimelineItemCount(): int
    {
        return $this->petition->timelineItems()->where('type', TimelineType::TAGS_CHANGED)->count();
    }

    private function checkedCheckboxPattern(Tag $tag): string
    {
        return sprintf('/value="%s"[^>]*\schecked/', $tag->id->toString());
    }
}
