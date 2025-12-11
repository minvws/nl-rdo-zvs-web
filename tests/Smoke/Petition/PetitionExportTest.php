<?php

declare(strict_types=1);

namespace Tests\Smoke\Petition;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\ExportType;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Petition;
use App\Models\PetitionExport;
use App\Models\PetitionType;
use App\Models\User;
use Tests\Smoke\SmokeTestCase;

use function __;
use function route;

class PetitionExportTest extends SmokeTestCase
{
    public function testCanExportToExcel(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create();

        Petition::factory()
            ->recycle($department)
            ->recycle($petitionType)
            ->create();

        DepartmentUser::factory()->create([
            'department_id' => $department->id,
            'user_id' => $user->id,
            'role' => DepartmentRole::WRITE,
        ]);

        $this->beUser($user)
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_INDEX, [
                'department' => $department,
            ])
            ->assertResponseStatus(200)
            ->select(ExportType::INTERNAL->value, 'export_type')
            ->select($petitionType->id, 'petition_type_id')
            ->type('1900-01-01', 'date_from')
            ->type('2400-01-01', 'date_to')
            ->press(__('exports.generate_export'))
            ->assertResponseStatus(200)
            ->see(__('petition.export_generated'))
            ->seeLink(__('exports.download'))
            ->see('01-01-1900')
            ->see(__('exports.' . ExportType::INTERNAL->value));
    }

    public function testCanResolveDownloadLink(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $department = Department::factory()->create();

        $export = PetitionExport::factory()
            ->recycle($department)
            ->create();

        DepartmentUser::factory()->create([
            'department_id' => $department->id,
            'user_id' => $user->id,
            'role' => DepartmentRole::WRITE,
        ]);

        $this->beUser($user)
            ->visitRoute(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_INDEX, [
                'department' => $department,
            ])
            ->assertResponseOk()
            ->see($export->date_from->format('d-m-Y'))
            ->see($export->date_to->format('d-m-Y'))
            ->seeLink(__('exports.download'), route(RouteName::DEPARTMENTS_PETITIONS_EXPORTS_DOWNLOAD, [
                'department' => $department,
                'petitionExport' => $export,
                'id' => $export->id,
            ]));
    }
}
