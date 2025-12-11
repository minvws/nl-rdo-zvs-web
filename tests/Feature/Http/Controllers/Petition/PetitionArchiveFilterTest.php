<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\ArchiveFilter;
use App\Enums\Authorization\Permission;
use App\Enums\PetitionCriteria;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\FeatureTestCase;

use function __;
use function now;

class PetitionArchiveFilterTest extends FeatureTestCase
{
    use RefreshDatabase;

    public function testIndexShowsNonArchivedPetitionsByDefault(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();

        $activePetition = Petition::factory()->create([
            'department_id' => $department->id,
            'archived_at' => null,
        ]);

        $archivedPetition = Petition::factory()->create([
            'department_id' => $department->id,
            'archived_at' => now(),
        ]);

        $response = $this->beUser($user, true, $department)->getByRoute('departments.petitions.index', [
            'department' => $department->slug,
        ]);

        $response->assertStatus(200);

        // By default, only active petitions should be shown
        $response->assertSee($activePetition->number);
        $response->assertDontSee($archivedPetition->number);

                // Check that the archive filter dropdown is present with hide_archived selected by default
        $response->assertSee('name="filter[archive]"', false);
        $response->assertSee('value="hide_archived"', false);
        $response->assertSee('selected>', false);
    }

    public function testIndexWithHideArchivedFilter(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();

        $activePetition = Petition::factory()->create([
            'department_id' => $department->id,
            'archived_at' => null,
        ]);

        $archivedPetition = Petition::factory()->create([
            'department_id' => $department->id,
            'archived_at' => now(),
        ]);

        $response = $this->beUser($user, true, $department)->getByRoute('departments.petitions.index', [
            'department' => $department->slug,
            'filter' => [
                PetitionCriteria::ARCHIVE->value => ArchiveFilter::HIDE_ARCHIVED->value,
            ],
        ]);

        $response->assertStatus(200);

        // Check that the archive filter is set correctly
        $response->assertSee('name="filter[archive]"', false);
        $response->assertSee('value="hide_archived"', false);
        $response->assertSee('selected>', false);

        // Only active petition should be shown
        $response->assertSee($activePetition->number);
        $response->assertDontSee($archivedPetition->number);
    }

    public function testIndexWithShowArchivedFilter(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();

        $activePetition = Petition::factory()->create([
            'department_id' => $department->id,
            'archived_at' => null,
        ]);

        $archivedPetition = Petition::factory()->create([
            'department_id' => $department->id,
            'archived_at' => now(),
        ]);

        $response = $this->beUser($user, true, $department)->getByRoute('departments.petitions.index', [
            'department' => $department->slug,
            'filter' => [
                PetitionCriteria::ARCHIVE->value => ArchiveFilter::SHOW_ARCHIVED->value,
            ],
        ]);

        $response->assertStatus(200);

        // Check that the archive filter is set correctly
        $response->assertSee('name="filter[archive]"', false);
        $response->assertSee('value="show_archived"', false);
        $response->assertSee('selected>', false);

        // The archived petition should be shown, active should not
        $response->assertSee($archivedPetition->number);
        $response->assertDontSee($activePetition->number);
    }

    public function testIndexWithShowAllFilter(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();

        $activePetition = Petition::factory()->create([
            'department_id' => $department->id,
            'archived_at' => null,
        ]);

        $archivedPetition = Petition::factory()->create([
            'department_id' => $department->id,
            'archived_at' => now(),
        ]);

        $response = $this->beUser($user, true, $department)->getByRoute('departments.petitions.index', [
            'department' => $department->slug,
            'filter' => [
                PetitionCriteria::ARCHIVE->value => ArchiveFilter::SHOW_ALL->value,
            ],
        ]);

        $response->assertStatus(200);

        // Check that the archive filter is set correctly
        $response->assertSee('name="filter[archive]"', false);
        $response->assertSee('value="show_all"', false);
        $response->assertSee('selected>', false); // Both petitions should be shown
        $response->assertSee($activePetition->number);
        $response->assertSee($archivedPetition->number);
    }

    public function testArchiveFilterIsVisibleInView(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();

        $response = $this->beUser($user, true, $department)->getByRoute('departments.petitions.index', [
            'department' => $department->slug,
        ]);

        $response->assertStatus(200);
        $response->assertSee(__('petition.filter.archive.label'));
        $response->assertSee(__('petition.filter.archive.hide_archived'));
        $response->assertSee(__('petition.filter.archive.show_archived'));
        $response->assertSee(__('petition.filter.archive.show_all'));
    }
}
