<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PetitionUnarchiveControllerTest extends FeatureTestCase
{
    #[Test]
    public function testUnarchivePetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => Carbon::now()->subDay(),
        ]);

        $this->assertNotNull($petition->archived_at);

        $user = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($user)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_UNARCHIVE_STORE, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]);

        $petition->refresh();
        $this->assertNull($petition->archived_at);
    }

    #[Test]
    public function testUnarchivePostRequiresWritePermission(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => Carbon::now()->subDay(),
        ]);

        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_UNARCHIVE_STORE, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertStatus(403);
    }
}
