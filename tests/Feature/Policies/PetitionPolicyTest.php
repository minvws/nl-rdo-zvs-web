<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Enums\Authorization\Permission;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PetitionPolicyTest extends FeatureTestCase
{
    #[Test]
    public function testCanUpdateNonArchivedPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($user, true, $department);

        $this->assertTrue($user->can('update', $petition));
    }

    #[Test]
    public function testCannotUpdateArchivedPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => Carbon::now(),
        ]);

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($user, true, $department);

        $this->assertFalse($user->can('update', $petition));
    }
}
