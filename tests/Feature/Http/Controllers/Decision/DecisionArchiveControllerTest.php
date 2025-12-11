<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Decision;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Decision;
use App\Models\Department;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DecisionArchiveControllerTest extends FeatureTestCase
{
    #[Test]
    public function testArchiveDecision(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $this->assertNull($decision->archived_at);

        $user = User::factory()->withPermissions(Permission::DECISION_WRITE)->fullyVerified()->create();
        $this->beUser($user)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_ARCHIVE_STORE, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $decision,
            ]);

        $decision->refresh();
        $this->assertNotNull($decision->archived_at);
    }

    #[Test]
    public function testArchivePostRequiresWritePermission(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_ARCHIVE_STORE, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertStatus(403);
    }
}
