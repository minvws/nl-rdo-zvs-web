<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Decision;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Decision;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DecisionUnarchiveControllerTest extends FeatureTestCase
{
    #[Test]
    public function testUnarchiveDecision(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create([
            'archived_at' => Carbon::now()->subDay(),
        ]);

        $this->assertNotNull($decision->archived_at);

        $user = User::factory()->withPermissions(Permission::DECISION_WRITE)->fullyVerified()->create();
        $this->beUser($user)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_UNARCHIVE_STORE, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $decision,
            ]);

        $decision->refresh();
        $this->assertNull($decision->archived_at);
    }

    #[Test]
    public function testUnarchivePostRequiresWritePermission(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create([
            'archived_at' => Carbon::now()->subDay(),
        ]);

        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_UNARCHIVE_STORE, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertStatus(403);
    }
}
