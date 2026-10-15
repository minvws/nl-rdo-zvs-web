<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Enums\TagColor;
use App\Models\Department;
use App\Models\Petition;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

use function __;
use function array_merge;
use function html_entity_decode;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_quote;
use function route;
use function sprintf;
use function str_repeat;

use const PHP_URL_QUERY;

class TagControllerTest extends FeatureTestCase
{
    public function testIndex(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create([
            'name' => 'Veelklager',
            'color' => TagColor::GRAPE,
        ]);

        $authUser = User::factory()->withPermissions(Permission::TAG_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->assertOk()
            ->assertViewIs('tags.index')
            ->assertViewHas('department', $department)
            ->assertSee($tag->name)
            ->assertSee('tag--color-grape', false);
    }

    public function testIndexShowsOnlyTagsOfOwnDepartment(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();

        $ownTag = Tag::factory()->recycle($department)->create(['name' => 'Eigen tag']);
        $otherTag = Tag::factory()->recycle($otherDepartment)->create(['name' => 'Andere tag']);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::TAG_READ)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->assertOk()
            ->assertSee($ownTag->name)
            ->assertDontSee($otherTag->name);
    }

    public function testIndexShowsActiveStateAndUsageCount(): void
    {
        $department = Department::factory()->create();
        $activeTag = Tag::factory()->recycle($department)->create(['name' => 'Actieve tag']);
        Tag::factory()->recycle($department)->inactive()->create(['name' => 'Inactieve tag']);

        $activeTag->petitions()->attach(Petition::factory()->recycle($department)->count(3)->create());

        $authUser = User::factory()->withPermissions(Permission::TAG_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->assertOk()
            ->assertSeeInOrder(['Actieve tag', __('general.yes'), '3', 'Inactieve tag', __('general.no'), '0']);

        $tags = $response->viewData('tags');
        $this->assertSame([3, 0], $tags->getCollection()->pluck('petitions_count')->all());
    }

    public function testIndexWithoutTags(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->assertOk()
            ->assertSeeText(__('tag.no_records'));
    }

    public function testIndexIsReadableForDepartmentReadRole(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();

        $authUser = User::factory()
            ->fullyVerified()
            ->withDepartmentRoles($department, DepartmentRole::READ)
            ->withLastVisitedDepartment($department)
            ->create();

        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->assertOk()
            ->assertSee($tag->name);
    }

    public function testIndexIsReadableForAdministrator(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();

        $authUser = User::factory()->asAdministrator()->create();

        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->assertOk()
            ->assertSee($tag->name);
    }

    public function testIndexRequiresPermission(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->assertForbidden();
    }

    public function testCategoryPermissionsDoNotGrantAccessToTags(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(
            Permission::PETITION_CATEGORY_READ,
            Permission::PETITION_CATEGORY_WRITE,
        )->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->assertForbidden();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $tag->id])
            ->assertForbidden();
    }

    public function testIndexShowsCreateAndEditLinksWithWritePermission(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_READ, Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->assertOk()
            ->assertSee(route(RouteName::DEPARTMENTS_ADMIN_TAGS_CREATE, ['department' => $department]), false)
            ->assertSee(route(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $tag->id]), false);
    }

    public function testIndexHidesCreateAndEditLinksWithoutWritePermission(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->assertOk()
            ->assertDontSee(route(RouteName::DEPARTMENTS_ADMIN_TAGS_CREATE, ['department' => $department]), false)
            ->assertDontSee(route(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $tag->id]), false);
    }

    public function testCreate(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_CREATE, ['department' => $department])
            ->assertOk()
            ->assertViewIs('tags.create')
            ->assertViewHas('department', $department)
            ->assertSeeText(__('tag_color.grape'));
    }

    public function testCreateRequiresWritePermission(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_CREATE, ['department' => $department])
            ->assertForbidden();
    }

    public function testStore(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_CREATE, ['department' => $department], [
                'name' => 'Veelklager',
                'color' => TagColor::ORANGE->value,
                'description' => 'Indiener die vaker terugkomt.',
                'active' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department]);

        $tag = Tag::query()->forDepartment($department)->sole();

        $this->assertSame('Veelklager', $tag->name);
        $this->assertSame(TagColor::ORANGE, $tag->color);
        $this->assertSame('Indiener die vaker terugkomt.', $tag->description);
        $this->assertTrue($tag->active);
    }

    public function testStoreInactiveWithoutDescription(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_CREATE, ['department' => $department], [
                'name' => 'Mediation',
                'color' => TagColor::BLUE->value,
                'active' => '0',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department]);

        $tag = Tag::query()->forDepartment($department)->sole();

        $this->assertNull($tag->description);
        $this->assertFalse($tag->active);
    }

    public function testStoreRejectsDuplicateNameRegardlessOfCase(): void
    {
        $department = Department::factory()->create();
        Tag::factory()->recycle($department)->create(['name' => 'Veelklager']);

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_CREATE, ['department' => $department], [
                'name' => 'veelklager',
                'color' => TagColor::BLUE->value,
                'active' => '1',
            ])
            ->assertSessionHasErrors(['name' => __('validation.unique_tag_name')]);

        $this->assertSame(1, Tag::query()->forDepartment($department)->count());
    }

    public function testStoreAllowsSameNameInOtherDepartment(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        Tag::factory()->recycle($otherDepartment)->create(['name' => 'Veelklager']);

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_CREATE, ['department' => $department], [
                'name' => 'Veelklager',
                'color' => TagColor::BLUE->value,
                'active' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department]);

        $this->assertSame(1, Tag::query()->forDepartment($department)->count());
    }

    /**
     * @param array<string, mixed> $override
     */
    #[DataProvider('invalidTagDataProvider')]
    public function testStoreRejectsInvalidData(array $override, string $expectedErrorField): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_CREATE, ['department' => $department], array_merge([
                'name' => 'Veelklager',
                'color' => TagColor::BLUE->value,
                'description' => null,
                'active' => '1',
            ], $override))
            ->assertSessionHasErrors($expectedErrorField);

        $this->assertSame(0, Tag::query()->forDepartment($department)->count());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidTagDataProvider(): array
    {
        return [
            'name missing' => [['name' => null], 'name'],
            'name not a string' => [['name' => ['Veelklager']], 'name'],
            'name too long' => [['name' => str_repeat('a', 65)], 'name'],
            'color missing' => [['color' => null], 'color'],
            'color unknown' => [['color' => 'chartreuse'], 'color'],
            'description too long' => [['description' => str_repeat('a', 256)], 'description'],
            'active missing' => [['active' => null], 'active'],
            'active not a boolean' => [['active' => 'ja'], 'active'],
        ];
    }

    public function testStoreRequiresWritePermission(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_CREATE, ['department' => $department], [
                'name' => 'Veelklager',
                'color' => TagColor::BLUE->value,
                'active' => '1',
            ])
            ->assertForbidden();

        $this->assertSame(0, Tag::query()->forDepartment($department)->count());
    }

    public function testEdit(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $tag->id])
            ->assertOk()
            ->assertViewIs('tags.edit')
            ->assertViewHas('department', $department)
            ->assertViewHas('tag', $tag)
            ->assertSee($tag->name);
    }

    public function testEditTagOfOtherDepartmentIsNotFound(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $tag = Tag::factory()->recycle($otherDepartment)->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $tag->id])
            ->assertNotFound();
    }

    public function testEditWithUnknownIdIsNotFound(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $this->faker->uuid()])
            ->assertNotFound();
    }

    public function testEditWithInvalidIdIsNotFound(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $this->faker->word()])
            ->assertNotFound();
    }

    public function testEditRequiresWritePermission(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $tag->id])
            ->assertForbidden();
    }

    public function testUpdate(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create([
            'name' => 'Veelklager',
            'color' => TagColor::ORANGE,
            'description' => null,
            'active' => true,
        ]);

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $tag->id], [
                'name' => 'Veelklager (oud)',
                'color' => TagColor::GRAY->value,
                'description' => 'Niet meer gebruiken.',
                'active' => '0',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department]);

        $tag->refresh();

        $this->assertSame('Veelklager (oud)', $tag->name);
        $this->assertSame(TagColor::GRAY, $tag->color);
        $this->assertSame('Niet meer gebruiken.', $tag->description);
        $this->assertFalse($tag->active);
        $this->assertTrue($tag->department->is($department));
    }

    public function testUpdateAllowsKeepingItsOwnName(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create([
            'name' => 'Veelklager',
            'color' => TagColor::ORANGE,
        ]);

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $tag->id], [
                'name' => 'Veelklager',
                'color' => TagColor::RED->value,
                'active' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department]);

        $this->assertSame(TagColor::RED, $tag->refresh()->color);
    }

    public function testUpdateRejectsNameOfAnotherTagInSameDepartment(): void
    {
        $department = Department::factory()->create();
        Tag::factory()->recycle($department)->create(['name' => 'Veelklager']);
        $tag = Tag::factory()->recycle($department)->create(['name' => 'Mediation']);

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $tag->id], [
                'name' => 'VEELKLAGER',
                'color' => $tag->color->value,
                'active' => '1',
            ])
            ->assertSessionHasErrors(['name' => __('validation.unique_tag_name')]);

        $this->assertSame('Mediation', $tag->refresh()->name);
    }

    public function testUpdateAllowsNameOfTagInOtherDepartment(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        Tag::factory()->recycle($otherDepartment)->create(['name' => 'Veelklager']);
        $tag = Tag::factory()->recycle($department)->create(['name' => 'Mediation']);

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $tag->id], [
                'name' => 'Veelklager',
                'color' => $tag->color->value,
                'active' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department]);

        $this->assertSame('Veelklager', $tag->refresh()->name);
    }

    /**
     * @param array<string, mixed> $override
     */
    #[DataProvider('invalidTagDataProvider')]
    public function testUpdateRejectsInvalidData(array $override, string $expectedErrorField): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create(['name' => 'Mediation']);

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $tag->id], array_merge([
                'name' => 'Veelklager',
                'color' => TagColor::BLUE->value,
                'description' => null,
                'active' => '1',
            ], $override))
            ->assertSessionHasErrors($expectedErrorField);

        $this->assertSame('Mediation', $tag->refresh()->name);
    }

    public function testUpdateTagOfOtherDepartmentIsNotFound(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $tag = Tag::factory()->recycle($otherDepartment)->create(['name' => 'Mediation']);

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $tag->id], [
                'name' => 'Veelklager',
                'color' => TagColor::BLUE->value,
                'active' => '1',
            ])
            ->assertNotFound();

        $this->assertSame('Mediation', $tag->refresh()->name);
    }

    public function testUpdateRequiresWritePermission(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create(['name' => 'Mediation']);

        $authUser = User::factory()->withPermissions(Permission::TAG_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_EDIT, ['department' => $department, 'tag' => $tag->id], [
                'name' => 'Veelklager',
                'color' => TagColor::BLUE->value,
                'active' => '1',
            ])
            ->assertForbidden();

        $this->assertSame('Mediation', $tag->refresh()->name);
    }

    public function testIndexShowsDeleteLinkWithUsageCountInConfirmation(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create(['name' => 'Veelklager']);
        $tag->petitions()->attach(Petition::factory()->recycle($department)->count(2)->create());

        $authUser = User::factory()->withPermissions(Permission::TAG_READ, Permission::TAG_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->assertOk()
            ->assertSee(__('tag.delete'));

        $confirmation = $this->extractConfirmation($response->getContent());

        $this->assertSame(
            route(RouteName::DEPARTMENTS_ADMIN_TAGS_DELETE, ['department' => $department, 'tag' => $tag->id]),
            Crypt::decryptString($confirmation['confirm_url']),
        );
        $this->assertSame(
            route(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department]),
            Crypt::decryptString($confirmation['cancel_url']),
        );
        $this->assertSame('DELETE', $confirmation['method']);
        $this->assertSame(
            'U gaat nu tag "Veelklager" verwijderen. De tag staat op 2 zaken en wordt daar weggehaald, ook in de API. Weet u het zeker?',
            $confirmation['message'],
        );
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function deleteConfirmationTextProvider(): array
    {
        return [
            'unused' => [0, 'U gaat nu tag "Veelklager" verwijderen, weet u het zeker?'],
            'used once' => [1, 'U gaat nu tag "Veelklager" verwijderen. De tag staat op 1 zaak en wordt daar weggehaald, ook in de API. Weet u het zeker?'],
        ];
    }

    #[DataProvider('deleteConfirmationTextProvider')]
    public function testIndexDeleteConfirmationTextFollowsUsageCount(int $usageCount, string $expectedMessage): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create(['name' => 'Veelklager']);
        $tag->petitions()->attach(Petition::factory()->recycle($department)->count($usageCount)->create());

        $authUser = User::factory()->withPermissions(Permission::TAG_READ, Permission::TAG_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->assertOk();

        $this->assertSame($expectedMessage, $this->extractConfirmation($response->getContent())['message']);
    }

    public function testIndexHidesDeleteLinkWithoutWritePermission(): void
    {
        $department = Department::factory()->create();
        Tag::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->assertOk()
            ->assertDontSee(__('tag.delete'))
            ->assertDontSee(route(RouteName::CONFIRM), false);
    }

    public function testDeleteUnusedTag(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->deleteByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_DELETE, ['department' => $department, 'tag' => $tag->id])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department])
            ->assertSessionHas('message.success', __('general.deleted'));

        $this->assertModelMissing($tag);
    }

    public function testDeleteTagThatIsStillAttachedToPetitions(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();
        $otherTag = Tag::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petition->tags()->attach([$tag->id, $otherTag->id]);

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->deleteByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_DELETE, ['department' => $department, 'tag' => $tag->id])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department]);

        $this->assertModelMissing($tag);
        $this->assertModelExists($petition);
        $remainingTags = $petition->tags()->get();
        $this->assertCount(1, $remainingTags);
        $this->assertTrue($remainingTags->contains($otherTag));
    }

    public function testDeleteTagOfOtherDepartmentIsNotFound(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $tag = Tag::factory()->recycle($otherDepartment)->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->deleteByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_DELETE, ['department' => $department, 'tag' => $tag->id])
            ->assertNotFound();

        $this->assertModelExists($tag);
    }

    public function testDeleteWithUnknownIdIsNotFound(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->deleteByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_DELETE, ['department' => $department, 'tag' => $this->faker->uuid()])
            ->assertNotFound();
    }

    public function testDeleteRequiresWritePermission(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::TAG_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->deleteByRoute(RouteName::DEPARTMENTS_ADMIN_TAGS_DELETE, ['department' => $department, 'tag' => $tag->id])
            ->assertForbidden();

        $this->assertModelExists($tag);
    }

    /**
     * @return array<string, string>
     */
    private function extractConfirmation(string $html): array
    {
        $pattern = sprintf('/href="(%s\?[^"]+)"/', preg_quote(route(RouteName::CONFIRM), '/'));
        $this->assertSame(1, preg_match($pattern, $html, $matches));

        parse_str((string) parse_url(html_entity_decode($matches[1]), PHP_URL_QUERY), $query);

        return $query;
    }
}
