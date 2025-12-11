<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Decision;

use App\Actions\Decision\DecisionArchiveAction;
use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Decision;
use App\Models\Department;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DecisionArchiveIntegrationTest extends FeatureTestCase
{
    #[Test]
    public function testDecisionBecomesReadOnlyAfterArchiving(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)->fullyVerified()->create();
        $user = $this->beUser($authUser, true, $department);
        $this->assertTrue($authUser->can('update', $decision));

        $archiveAction = $this->app->make(DecisionArchiveAction::class);
        $archiveAction->execute($decision, $authUser);

        $decision->refresh();

        $this->assertFalse($authUser->can('update', $decision));
        $this->assertNotNull($decision->archived_at);

        $user->getByRoute(RouteName::DEPARTMENTS_DECISIONS_EDIT, [
            'department' => $department,
            'decision' => $decision,
        ])->assertForbidden();
    }
}
