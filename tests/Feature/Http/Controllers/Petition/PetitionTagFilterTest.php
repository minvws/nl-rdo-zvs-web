<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Actions\Tag\DeleteTagAction;
use App\Enums\Authorization\Permission;
use App\Enums\PetitionCriteria;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserDepartmentFilter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\FeatureTestCase;

use function preg_quote;
use function sprintf;
use function str_contains;

class PetitionTagFilterTest extends FeatureTestCase
{
    private const string FILTER_NAME = 'name="filter[tag]"';

    public function testFilterUiShowsTagsInUseByDepartment(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->for($department)->create(['name' => 'Spoedzaak']);
        Petition::factory()->recycle($department)->create()->tags()->attach($tag);

        $this->beUser($this->createUser($department), true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department->slug])
            ->assertOk()
            ->assertSee(self::FILTER_NAME, false)
            ->assertSee(sprintf('value="%s"', $tag->id->toString()), false)
            ->assertSee('Spoedzaak');
    }

    public function testFilterUiDoesNotShowTagsFromOtherDepartments(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $ownTag = Tag::factory()->for($department)->create(['name' => 'Eigen tag']);
        $otherTag = Tag::factory()->for($otherDepartment)->create(['name' => 'Andere afdeling tag']);
        Petition::factory()->recycle($department)->create()->tags()->attach($ownTag);
        Petition::factory()->recycle($otherDepartment)->create()->tags()->attach($otherTag);

        $this->beUser($this->createUser($department), true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department->slug])
            ->assertOk()
            ->assertSee('Eigen tag')
            ->assertDontSee('Andere afdeling tag');
    }

    public function testFilterUiDoesNotOfferUnusedTags(): void
    {
        $department = Department::factory()->create();
        $usedTag = Tag::factory()->for($department)->create(['name' => 'Gebruikte tag']);
        Tag::factory()->for($department)->create(['name' => 'Ongebruikte tag']);
        Petition::factory()->recycle($department)->create()->tags()->attach($usedTag);

        $this->beUser($this->createUser($department), true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department->slug])
            ->assertOk()
            ->assertSee('Gebruikte tag')
            ->assertDontSee('Ongebruikte tag');
    }

    public function testFilterUiOffersInactiveTagsThatAreStillInUse(): void
    {
        $department = Department::factory()->create();
        $inactiveTag = Tag::factory()->for($department)->inactive()->create(['name' => 'Oude tag']);
        Petition::factory()->recycle($department)->create()->tags()->attach($inactiveTag);

        $this->beUser($this->createUser($department), true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department->slug])
            ->assertOk()
            ->assertSee(sprintf('value="%s"', $inactiveTag->id->toString()), false)
            ->assertSee('Oude tag');
    }

    public function testFilterUiHidesTagFilterWhenNoTagIsInUse(): void
    {
        $department = Department::factory()->create();
        Tag::factory()->for($department)->create();
        Petition::factory()->recycle($department)->create();

        $this->beUser($this->createUser($department), true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department->slug])
            ->assertOk()
            ->assertDontSee(self::FILTER_NAME, false);
    }

    public function testFilterFiltersCorrectlyOnTag(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->for($department)->create();
        $otherTag = Tag::factory()->for($department)->create();
        Petition::factory()->recycle($department)->create(['number' => 'REF-WITH-TAG'])->tags()->attach($tag);
        Petition::factory()->recycle($department)->create(['number' => 'REF-OTHER-TAG'])->tags()->attach($otherTag);
        Petition::factory()->recycle($department)->create(['number' => 'REF-WITHOUT-TAG']);

        $response = $this->beUser($this->createUser($department), true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department->slug,
                'filter' => [PetitionCriteria::TAG->value => $tag->id->toString()],
            ])
            ->assertOk()
            ->assertSee('REF-WITH-TAG')
            ->assertDontSee('REF-OTHER-TAG')
            ->assertDontSee('REF-WITHOUT-TAG');

        $this->assertMatchesRegularExpression(
            sprintf('/value="%s"\s+selected>/', preg_quote($tag->id->toString(), '/')),
            (string) $response->getContent(),
        );
        $this->assertDoesNotMatchRegularExpression(
            sprintf('/value="%s"\s+selected>/', preg_quote($otherTag->id->toString(), '/')),
            (string) $response->getContent(),
        );
    }

    public function testFilterCombinesWithSearch(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->for($department)->create();
        Petition::factory()->recycle($department)->create(['number' => 'ZOEK-001'])->tags()->attach($tag);
        Petition::factory()->recycle($department)->create(['number' => 'ANDERS-002'])->tags()->attach($tag);
        Petition::factory()->recycle($department)->create(['number' => 'ZOEK-003']);

        $this->beUser($this->createUser($department), true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department->slug,
                'filter' => [
                    PetitionCriteria::TAG->value => $tag->id->toString(),
                    PetitionCriteria::SEARCH->value => 'ZOEK',
                ],
            ])
            ->assertOk()
            ->assertSee('ZOEK-001')
            ->assertDontSee('ANDERS-002')
            ->assertDontSee('ZOEK-003');
    }

    public function testSavedFilterOnDeletedTagIsIgnored(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->for($department)->create();
        Petition::factory()->recycle($department)->create(['number' => 'REF-WITH-TAG'])->tags()->attach($tag);
        Petition::factory()->recycle($department)->create(['number' => 'REF-WITHOUT-TAG']);
        $user = $this->createUser($department);

        UserDepartmentFilter::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
            'filter_data' => [PetitionCriteria::TAG->value => $tag->id->toString()],
        ]);
        $this->app->make(DeleteTagAction::class)->execute($tag);

        $response = $this->beUser($user, true, $department)
            ->followingRedirects()
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department->slug])
            ->assertOk()
            ->assertSee('REF-WITH-TAG')
            ->assertSee('REF-WITHOUT-TAG');

        $this->assertTagFilterIsNotApplied($response);
    }

    public function testFilterOnTagOfAnotherDepartmentIsIgnored(): void
    {
        $department = Department::factory()->create();
        $otherTag = Tag::factory()->for(Department::factory())->create();
        Petition::factory()->recycle($department)->create(['number' => 'REF-OWN']);

        $response = $this->beUser($this->createUser($department), true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department->slug,
                'filter' => [PetitionCriteria::TAG->value => $otherTag->id->toString()],
            ])
            ->assertOk()
            ->assertSee('REF-OWN');

        $this->assertTagFilterIsNotApplied($response);
    }

    public function testFilterOnValueThatIsNotATagIdIsIgnored(): void
    {
        $department = Department::factory()->create();
        Petition::factory()->recycle($department)->create(['number' => 'REF-OWN']);

        $response = $this->beUser($this->createUser($department), true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department->slug,
                'filter' => [PetitionCriteria::TAG->value => 'not-a-tag-id'],
            ])
            ->assertOk()
            ->assertSee('REF-OWN');

        $this->assertTagFilterIsNotApplied($response);
    }

    /**
     * @param TestResponse<Response> $response
     */
    private function assertTagFilterIsNotApplied(TestResponse $response): void
    {
        $response->assertViewHas(
            'paginator',
            static fn(LengthAwarePaginator $paginator): bool => !str_contains(
                $paginator->url(1),
                sprintf('filter%%5B%s%%5D', PetitionCriteria::TAG->value),
            ),
        );
    }

    private function createUser(Department $department): User
    {
        return User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_READ)
            ->fullyVerified()
            ->create();
    }
}
