<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Actions\Petition\PetitionArchiveAction;
use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PetitionArchiveIntegrationTest extends FeatureTestCase
{
    #[Test]
    public function testPetitionBecomesReadOnlyAfterArchiving(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $user = $this->beUser($authUser, true, $department);
        $this->assertTrue($authUser->can('update', $petition));

        $archiveAction = $this->app->make(PetitionArchiveAction::class);
        $archiveAction->execute($petition, $authUser);
        $petition->refresh();

        $this->assertFalse($authUser->can('update', $petition));
        $this->assertNotNull($petition->archived_at);

        $user->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ])->assertForbidden();

        $user->getByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ])->assertForbidden();

        $user->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ])->assertForbidden();
    }
}
