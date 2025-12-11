<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\ExportType;
use App\Enums\PetitionTypeType;
use App\Enums\RouteName;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionExport;
use App\Models\PetitionStatusHistory;
use App\Models\PetitionType;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function __;
use function sprintf;

class PetitionExportControllerTest extends FeatureTestCase
{
    public function testIndex(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_INDEX, ['department' => $department])
            ->assertOk()
            ->assertSee(__('petition.no_exports'))
            ->assertViewIs('petition.exports.index');
    }

    public function testIndexShowsOnlyDepartmentPetitionExports(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $export1 = PetitionExport::factory()->recycle($department1)->create([
            'type' => ExportType::INTERNAL->value,
        ]);
        $export2 = PetitionExport::factory()->recycle($department2)->create([
            'type' => ExportType::DASHBOARD->value,
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department1, Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_INDEX, ['department' => $department1])
            ->assertOk()
            ->assertSeeHtml('<td>' . __('exports.' . $export1->type->value) . '</td>')
            ->assertDontSeeHtml('<td>' . __('exports.' . $export2->type->value) . '</td>');
    }

    public function testExport(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $type = $this->faker()->randomElement(ExportType::cases());

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_EXPORTS_EXPORT,
                [
                    'department' => $department,
                ],
                [
                    'export_type' => $type->value,
                    'petition_type_id' => $petition->petition_type_id->toString(),
                    'date_from' => '1900-01-01',
                    'date_to' => '2400-01-01',
                ],
            )
            ->assertRedirect();

            $this->assertDatabaseHas('petition_exports', [
                'type' => $type->value,
                'petition_type_id' => $petition->petition_type_id,
                'date_from' => '1900-01-01',
                'date_to' => '2400-01-01',
            ]);
    }

    public function testDownload(): void
    {
        $disk = 'exports';
        Storage::fake($disk);
        $department = Department::factory()->create();
        $export = PetitionExport::factory()
            ->recycle($department)
            ->create([
                'disk' => $disk,
            ]);

        $filename = sprintf('%s.%s', $export->id->toString(), 'xlsx');
        Storage::disk($disk)->put($filename, $this->faker->word());

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE, Permission::PETITION_READ,)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_DOWNLOAD, [
                'department' => $department,
                'petitionExport' => $export,
            ])
            ->assertSessionHasNoErrors()
            ->assertDownload($filename);
    }

    public function testDownloadFailsNonExisting(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_DOWNLOAD, [
                'department' => $department,
                'petitionExport' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    public function testDelete(): void
    {
        $department = Department::factory()->create();
        $export = PetitionExport::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_DELETE, [
                'department' => $department,
                'petitionExport' => $export,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('petition_exports', ['id' => $export->id]);
    }

    #[DataProvider('typeProvider')]
    public function testCreateInternalExport(ExportType $type, PetitionTypeType $petitionTypeType): void
    {
        $date = $this->faker->calendarDate();
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => $petitionTypeType->value,
        ]);
        $petition = Petition::factory()->recycle($department)->create(
            [
                'date_of_entry' => $date->toDateString(),
                'petition_type_id' => $petitionType->id,
            ],
        );
        $petition->decisions()->sync([$decision->id->toString()]);
        PetitionStatusHistory::factory()->count(3)->recycle($petition)->create([
            'date' => $date->toDateString(),
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_EXPORTS_EXPORT,
                [
                    'department' => $department,
                ],
                [
                    'export_type' => $type->value,
                    'petition_type_id' => $petition->petition_type_id->toString(),
                    'date_from' => $date->toDateString(),
                    'date_to' => $date->toDateString(),
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas('petition_exports', [
            'type' => $type->value,
            'petition_type_id' => $petition->petition_type_id,
            'date_from' => $date->toDateString(),
            'date_to' => $date->toDateString(),
        ]);
    }

    #[DataProvider('typeProvider')]
    public function testCreateExportWithCategory(ExportType $type, PetitionTypeType $petitionTypeType): void
    {
        $department = Department::factory()->create();
        $petitionCategory = PetitionCategory::factory()->recycle($department)->create();
        $date = $this->faker->calendarDate();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => $petitionTypeType->value,
        ]);
        $petition = Petition::factory()
            ->recycle($department)
            ->recycle($petitionType)
            ->recycle($petitionCategory)
            ->create(
                [
                    'date_of_entry' => $date->toDateString(),
                ],
            );
        PetitionStatusHistory::factory()->count(3)->recycle($petition)->create([
            'date' => $date->toDateString(),
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_EXPORTS_EXPORT,
                [
                    'department' => $department,
                ],
                [
                    'export_type' => $type->value,
                    'petition_type_id' => $petition->petition_type_id->toString(),
                    'petition_category_id' => $petitionCategory->id->toString(),
                    'date_from' => $date->toDateString(),
                    'date_to' => $date->toDateString(),
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas('petition_exports', [
            'type' => $type->value,
            'petition_type_id' => $petition->petition_type_id,
            'petition_category_id' => $petition->petition_category_id,
            'date_from' => $date->toDateString(),
            'date_to' => $date->toDateString(),
        ]);
    }

    /**
     * @return array<mixed>
     */
    public static function typeProvider(): array
    {
        return [
            'set 1' => [ExportType::INTERNAL, PetitionTypeType::WOO_VERZOEK],
            'set 2' => [ExportType::DASHBOARD, PetitionTypeType::WOO_VERZOEK],
            'set 3' => [ExportType::INTERNAL, PetitionTypeType::BEZWAAR],
            'set 4' => [ExportType::DASHBOARD, PetitionTypeType::BEZWAAR],
            'set 5' => [ExportType::INTERNAL, PetitionTypeType::BEROEP],
            'set 6' => [ExportType::DASHBOARD, PetitionTypeType::BEROEP],
        ];
    }

    #[Test]
    public function testPetitionExportCrossDepartmentVulnerability(): void
    {
        $departmentA = Department::factory()->create(['name' => 'Department A']);
        $departmentB = Department::factory()->create(['name' => 'Department B']);

        $exportFromDepartmentA = PetitionExport::factory()
            ->recycle($departmentA)
            ->create();

        $userFromDepartmentB = User::factory()
            ->withPermissionsAndDepartment($departmentB, Permission::PETITION_READ)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($userFromDepartmentB, true, $departmentB)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_DOWNLOAD, [
                'department' => $departmentB->slug,
                'petitionExport' => $exportFromDepartmentA->id,
            ]);

        $response->assertNotFound();
    }
}
