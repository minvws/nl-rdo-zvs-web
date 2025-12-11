<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Decision;

use App\Enums\Authorization\Permission;
use App\Enums\DecisionCriteria;
use App\Enums\DecisionType;
use App\Models\Decision;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\FeatureTestCase;

use function __;
use function now;

class DecisionTypeFilterTest extends FeatureTestCase
{
    use RefreshDatabase;

    public function testIndexWithChatTypeFilter(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();

        $chatDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'type' => DecisionType::CHAT,
        ]);

        $regularDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'type' => DecisionType::REGULAR,
        ]);

        $response = $this->beUser($user, true, $department)->getByRoute('departments.decisions.index', [
            'department' => $department->slug,
            'filter' => [
                DecisionCriteria::TYPE->value => DecisionType::CHAT->value,
            ],
        ]);

        $response->assertStatus(200);

        $response->assertSee('name="filter[type]"', false);
        $response->assertSee('value="' . DecisionType::CHAT->value . '"', false);
        $response->assertSee('selected>', false);

        $response->assertSee($chatDecision->name);
        $response->assertDontSee($regularDecision->name);
    }

    public function testIndexWithRegularTypeFilter(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();

        $chatDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'type' => DecisionType::CHAT,
        ]);

        $regularDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'type' => DecisionType::REGULAR,
        ]);

        $response = $this->beUser($user, true, $department)->getByRoute('departments.decisions.index', [
            'department' => $department->slug,
            'filter' => [
                DecisionCriteria::TYPE->value => DecisionType::REGULAR->value,
            ],
        ]);

        $response->assertStatus(200);

        $response->assertSee('name="filter[type]"', false);
        $response->assertSee('value="' . DecisionType::REGULAR->value . '"', false);
        $response->assertSee('selected>', false);

        $response->assertSee($regularDecision->name);
        $response->assertDontSee($chatDecision->name);
    }

    public function testIndexWithoutTypeFilterShowsAllTypes(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();

        $chatDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'type' => DecisionType::CHAT,
        ]);

        $regularDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'type' => DecisionType::REGULAR,
        ]);

        $response = $this->beUser($user, true, $department)->getByRoute('departments.decisions.index', [
            'department' => $department->slug,
        ]);

        $response->assertStatus(200);

        $response->assertSee($chatDecision->name);
        $response->assertSee($regularDecision->name);
    }

    public function testTypeFilterIsVisibleInView(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();

        $response = $this->beUser($user, true, $department)->getByRoute('departments.decisions.index', [
            'department' => $department->slug,
        ]);

        $response->assertStatus(200);
        $response->assertSee(__('decision.filter.type.label'));
        $response->assertSee(__('decision.filter.type.all'));
        $response->assertSee(__('decision.type.chat'));
        $response->assertSee(__('decision.type.regular'));
    }

    public function testTypeFilterCombinesWithArchiveFilter(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();

        $chatDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'type' => DecisionType::CHAT,
            'archived_at' => null,
        ]);

        $archivedChatDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'type' => DecisionType::CHAT,
            'archived_at' => now(),
        ]);

        $regularDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'type' => DecisionType::REGULAR,
            'archived_at' => null,
        ]);

        $response = $this->beUser($user, true, $department)->getByRoute('departments.decisions.index', [
            'department' => $department->slug,
            'filter' => [
                DecisionCriteria::TYPE->value => DecisionType::CHAT->value,
            ],
        ]);

        $response->assertStatus(200);

        $response->assertSee($chatDecision->name);
        $response->assertDontSee($archivedChatDecision->name);
        $response->assertDontSee($regularDecision->name);
    }
}
