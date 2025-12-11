<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class PetitionAttachControllerBladeConfirmTest extends FeatureTestCase
{
    #[Test]
    public function testDetachPetitionStillWorks(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $relatedPetition = Petition::factory()->create();

        DB::table('petition_petition')->insert([
            'petition_id' => $petition->id,
            'related_petition_id' => $relatedPetition->id,
        ]);

        $user = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($user)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITION_PETITION_DETACH,
                [
                    'department' => $department,
                    'petition' => $petition,
                    'relatedPetition' => $relatedPetition,
                ],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ]);

        $this->assertDatabaseMissing('petition_petition', [
            'petition_id' => $petition->id,
            'related_petition_id' => $relatedPetition->id,
        ]);
    }

    #[Test]
    public function testPetitionShowViewRendersWithConfirmRoutes(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create(['number' => 'P-2024-001']);
        $relatedPetition = Petition::factory()->create(['number' => 'P-2024-002']);

        DB::table('petition_petition')->insert([
            'petition_id' => $petition->id,
            'related_petition_id' => $relatedPetition->id,
        ]);

        $user = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_READ,
            Permission::PETITION_WRITE,
        )->fullyVerified()->create();

        $response = $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ]);

        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('/confirm', $content);
        $this->assertStringContainsString('loskoppelen', $content);
    }
}
