<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

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
        Petition::factory()
            ->recycle($department)
            ->create([
                'assigned_to' => $user->id,
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
}
