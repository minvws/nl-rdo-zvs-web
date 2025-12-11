<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Decision;

use App\Enums\ArchiveFilter;
use App\Enums\Authorization\Permission;
use App\Enums\DecisionCriteria;
use App\Models\Decision;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\FeatureTestCase;

use function __;
use function now;

class DecisionArchiveFilterTest extends FeatureTestCase
{
    use RefreshDatabase;

    public function testIndexShowsNonArchivedDecisionsByDefault(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();

        $activeDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'archived_at' => null,
        ]);

        $archivedDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'archived_at' => now(),
        ]);

        $response = $this->beUser($user, true, $department)->getByRoute('departments.decisions.index', [
            'department' => $department->slug,
        ]);

        $response->assertStatus(200);

        $response->assertSee($activeDecision->name);
        $response->assertDontSee($archivedDecision->name);

        $response->assertSee('name="filter[archive]"', false);
        $response->assertSee('value="hide_archived"', false);
        $response->assertSee('selected>', false);
    }

    public function testIndexWithHideArchivedFilter(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();

        $activeDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'archived_at' => null,
        ]);

        $archivedDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'archived_at' => now(),
        ]);

        $response = $this->beUser($user, true, $department)->getByRoute('departments.decisions.index', [
            'department' => $department->slug,
            'filter' => [
                DecisionCriteria::ARCHIVE->value => ArchiveFilter::HIDE_ARCHIVED->value,
            ],
        ]);

        $response->assertStatus(200);

        $response->assertSee('name="filter[archive]"', false);
        $response->assertSee('value="hide_archived"', false);
        $response->assertSee('selected>', false);

        $response->assertSee($activeDecision->name);
        $response->assertDontSee($archivedDecision->name);
    }

    public function testIndexWithShowArchivedFilter(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();

        $activeDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'archived_at' => null,
        ]);

        $archivedDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'archived_at' => now(),
        ]);

        $response = $this->beUser($user, true, $department)->getByRoute('departments.decisions.index', [
            'department' => $department->slug,
            'filter' => [
                DecisionCriteria::ARCHIVE->value => ArchiveFilter::SHOW_ARCHIVED->value,
            ],
        ]);

        $response->assertStatus(200);

        $response->assertSee('name="filter[archive]"', false);
        $response->assertSee('value="show_archived"', false);
        $response->assertSee('selected>', false);

        $response->assertSee($archivedDecision->name);
        $response->assertDontSee($activeDecision->name);
    }

    public function testIndexWithShowAllFilter(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();

        $activeDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'archived_at' => null,
        ]);

        $archivedDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'archived_at' => now(),
        ]);

        $response = $this->beUser($user, true, $department)->getByRoute('departments.decisions.index', [
            'department' => $department->slug,
            'filter' => [
                DecisionCriteria::ARCHIVE->value => ArchiveFilter::SHOW_ALL->value,
            ],
        ]);

        $response->assertStatus(200);

        $response->assertSee('name="filter[archive]"', false);
        $response->assertSee('value="show_all"', false);
        $response->assertSee('selected>', false);

        $response->assertSee($activeDecision->name);
        $response->assertSee($archivedDecision->name);
    }

    public function testArchiveFilterIsVisibleInView(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();

        $response = $this->beUser($user, true, $department)->getByRoute('departments.decisions.index', [
            'department' => $department->slug,
        ]);

        $response->assertStatus(200);
        $response->assertSee(__('decision.filter.archive.label'));
        $response->assertSee(__('decision.filter.archive.hide_archived'));
        $response->assertSee(__('decision.filter.archive.show_archived'));
        $response->assertSee(__('decision.filter.archive.show_all'));
    }
}
