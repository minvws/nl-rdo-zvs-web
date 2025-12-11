<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PetitionArchiveControllerTest extends FeatureTestCase
{
    #[Test]
    public function testArchivePetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $this->assertNull($petition->archived_at);

        $user = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($user)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ARCHIVE_STORE, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]);

        $petition->refresh();
        $this->assertNotNull($petition->archived_at);
    }

    #[Test]
    public function testArchivePostRequiresWritePermission(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ARCHIVE_STORE, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertStatus(403);
    }
}
