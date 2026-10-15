<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\AssignmentRole;
use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Enums\TagColor;
use App\Models\Department;
use App\Models\Petition;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

use function __;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function sprintf;
use function strtolower;
use function trans_choice;
use function trim;

class PetitionIndexControllerTest extends FeatureTestCase
{
    public function testIndexShowsOnlyDepartmentPetitions(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $petition1 = Petition::factory()->recycle($department1)->create([
            'number' => 'REF-DEPT-1-001',
        ]);
        $petition2 = Petition::factory()->recycle($department2)->create([
            'number' => 'REF-DEPT-2-001',
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department1, Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department1->slug,
            ])
            ->assertOk()
            ->assertSee($petition1->number)
            ->assertDontSee($petition2->number);
    }

    #[DataProvider('petitionIndexInvalidSortParametersDataProvider')]
    public function testIndexWithInvalidSortParameters(?array $sortParameters): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $petition->assignments()->create([
            'user_id' => $user->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department,
                'sort' => $sortParameters,
            ])
            ->assertBadRequest();
    }

    public static function petitionIndexInvalidSortParametersDataProvider(): array
    {
        return [
            [['date_of_entry' => 'fake']],
            [['date_of_entry' => 'fake:asc']],
            [['policy_department_id' => 'fake:desc']],
            [['updated_at' => 'fake:asc']],
        ];
    }

    public function testIndexWithSearchFilterShowsOnlyDepartmentPetitions(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();
        $searchTerm = Str::random(8);

        $petition1 = Petition::factory()->recycle($department1)->create([
            'number' => 'REF-DEPT1-' . $searchTerm,
        ]);
        $petition2 = Petition::factory()->recycle($department2)->create([
            'number' => 'REF-DEPT2-' . $searchTerm,
        ]);
        $petition3 = Petition::factory()->recycle($department1)->create([
            'number' => 'REF-DEPT1-OTHER',
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department1, Permission::PETITION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department1->slug,
                'filter' => [
                    'search' => $searchTerm,
                ],
            ]);

        $response->assertOk()
            ->assertSee($petition1->number)
            ->assertDontSee($petition2->number)
            ->assertDontSee($petition3->number);
    }

    public function testIndexWithSearchFilterRespectsDepartmentIsolationDept1(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();
        $searchTerm = 'CommonSearch' . Str::random(5);

        Petition::factory()->recycle($department1)->create([
            'number' => $searchTerm . '-DEPT1',
        ]);
        Petition::factory()->recycle($department2)->create([
            'number' => $searchTerm . '-DEPT2',
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department1, Permission::PETITION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department1->slug,
                'filter' => [
                    'search' => $searchTerm,
                ],
            ]);

        $response->assertOk()
            ->assertSee('-DEPT1')
            ->assertDontSee('-DEPT2');
    }

    public function testIndexWithSearchFilterRespectsDepartmentIsolationDept2(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();
        $searchTerm = 'CommonSearch' . Str::random(5);

        Petition::factory()->recycle($department1)->create([
            'number' => $searchTerm . '-DEPT1',
        ]);
        Petition::factory()->recycle($department2)->create([
            'number' => $searchTerm . '-DEPT2',
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department2, Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $department2)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department2->slug,
                'filter' => [
                    'search' => $searchTerm,
                ],
            ])
            ->assertOk()
            ->assertSee('-DEPT2')
            ->assertDontSee('-DEPT1');
    }

    public function testIndexShowsTheDeadlineOverrideInTheDeadlineColumn(): void
    {
        $department = Department::factory()->create();
        Petition::factory()->recycle($department)->create([
            'deadline_at' => '2026-03-01',
            'deadline_override_at' => '2026-06-30',
            'deadline_override_reason' => 'Afspraak met verzoeker',
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department->slug,
            ])
            ->assertOk()
            ->assertSee('30-06-2026')
            ->assertSee(__('petition.deadline_override_legend'))
            ->assertDontSee('Afspraak met verzoeker')
            ->assertDontSee('01-03-2026');
    }

    public function testIndexShowsTheCalculatedDeadlineWithoutAnOverride(): void
    {
        $department = Department::factory()->create();
        Petition::factory()->recycle($department)->create([
            'deadline_at' => '2026-03-01',
            'deadline_override_at' => null,
            'deadline_override_reason' => null,
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department->slug,
            ])
            ->assertOk()
            ->assertSee('01-03-2026')
            ->assertDontSee(__('petition.deadline_override_legend'));
    }

    public function testIndexShowsTheTagsOfAPetitionAsColouredChips(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $tags = $this->createTags($department, ['Alfa' => TagColor::RED, 'Bravo' => TagColor::GREEN]);
        $petition->tags()->attach($tags);

        $cell = $this->tagCellOfPetitionIndex($department);

        $this->assertStringContainsString('<span class="tag tag--color-red">Alfa</span>', $cell);
        $this->assertStringContainsString('<span class="tag tag--color-green">Bravo</span>', $cell);
    }

    public function testIndexShowsThreeTagsWithoutACounter(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petition->tags()->attach($this->createTags($department, [
            'Alfa' => TagColor::BLUE,
            'Bravo' => TagColor::BLUE,
            'Charlie' => TagColor::BLUE,
        ]));

        $cell = $this->tagCellOfPetitionIndex($department);

        $this->assertStringContainsString('>Alfa</span>', $cell);
        $this->assertStringContainsString('>Bravo</span>', $cell);
        $this->assertStringContainsString('>Charlie</span>', $cell);
        $this->assertStringNotContainsString('+', $cell);
    }

    public function testIndexShowsThreeTagsAndACounterForTheRest(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petition->tags()->attach($this->createTags($department, [
            'Echo' => TagColor::BLUE,
            'Delta' => TagColor::BLUE,
            'Charlie' => TagColor::BLUE,
            'Bravo' => TagColor::BLUE,
            'Alfa' => TagColor::BLUE,
        ]));

        $cell = $this->tagCellOfPetitionIndex($department);

        $this->assertStringContainsString('<span class="tag tag--color-blue">Alfa</span>', $cell);
        $this->assertStringContainsString('<span class="tag tag--color-blue">Bravo</span>', $cell);
        $this->assertStringContainsString('<span class="tag tag--color-blue">Charlie</span>', $cell);
        $this->assertStringNotContainsString('>Delta</span>', $cell);
        $this->assertStringNotContainsString('>Echo</span>', $cell);
        $this->assertMatchesRegularExpression(
            sprintf(
                '/<span\s+class="tag tag--color-gray"\s+title="Delta, Echo">\s*<span aria-hidden="true">\+2<\/span>\s*<span class="visually-hidden">\s*%s\s*<\/span>/',
                preg_quote(trans_choice('tag.more', 2, ['names' => 'Delta, Echo']), '/'),
            ),
            $cell,
        );
    }

    public function testIndexShowsADashForAPetitionWithoutTags(): void
    {
        $department = Department::factory()->create();
        Petition::factory()->recycle($department)->create();

        $this->assertSame('-', $this->tagCellOfPetitionIndex($department));
    }

    public function testIndexEmptyStateSpansAllColumns(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();
        $content = (string) $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department->slug,
            ])
            ->assertOk()
            ->getContent();

        $this->assertSame(1, preg_match('/<thead>(.*?)<\/thead>/s', $content, $head));
        $columnCount = preg_match_all('/<th\b/', $head[1]);
        $this->assertSame(1, preg_match('/colspan="(\d+)"\s+class="no-results"/', $content, $colspan));
        $this->assertSame($columnCount, (int) $colspan[1]);
    }

    /**
     * @param array<string, TagColor> $colorsByName
     *
     * @return array<int, Tag>
     */
    private function createTags(Department $department, array $colorsByName): array
    {
        $tags = [];
        foreach ($colorsByName as $name => $color) {
            $tags[] = Tag::factory()->for($department)->create(['name' => $name, 'color' => $color]);
        }

        return $tags;
    }

    private function tagCellOfPetitionIndex(Department $department): string
    {
        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();
        $content = (string) $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department->slug,
            ])
            ->assertOk()
            ->getContent();

        $pattern = sprintf('/<td\s+data-hide-table-column-content="%s">(.*?)<\/td>/s', strtolower(__('tag.model_plural')));
        $this->assertSame(1, preg_match($pattern, $content, $matches));

        return trim($matches[1]);
    }
}
