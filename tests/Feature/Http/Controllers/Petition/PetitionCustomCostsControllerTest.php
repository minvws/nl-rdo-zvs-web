<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\CustomCostType;
use App\Enums\RouteName;
use App\Models\CustomCost;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionType;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Tests\Feature\FeatureTestCase;

use function __;

class PetitionCustomCostsControllerTest extends FeatureTestCase
{
    public function testEditCustomCosts(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_COSTS_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertStatus(200);
    }

    public function testEditCustomCostsWithNonExistingPetition(): void
    {
        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_COSTS_EDIT, [
                'department' => $this->faker()->word(),
                'petition' => $this->faker()->uuid(),
            ])->assertNotFound();
    }

    public function testViewCustomCosts(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        // Configure cost types for this petition type
        Config::set('custom_cost.' . $petitionType->type->value, [
            CustomCostType::LEGAL_COSTS->value,
            CustomCostType::COURT_FEES->value,
        ]);

        // Create custom costs
        CustomCost::factory()->create([
            'petition_id' => $petition->id,
            'custom_cost_type' => CustomCostType::LEGAL_COSTS,
            'custom_cost_amount_in_cents' => 100_001,
        ]);

        CustomCost::factory()->create([
            'petition_id' => $petition->id,
            'custom_cost_type' => CustomCostType::COURT_FEES,
            'custom_cost_amount_in_cents' => 50_001,
        ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_COSTS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertSee(__('petition.custom_cost'))
            ->assertSee('1.000,01')
            ->assertSee('500,01');
    }

    public function testUpdateCustomCostsWithHtmx(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        // Configure cost types for this petition type
        Config::set('custom_cost.' . $petitionType->type->value, [
            CustomCostType::LEGAL_COSTS->value,
            CustomCostType::COURT_FEES->value,
        ]);

        $customCosts = [
            [
                'custom_cost_type' => CustomCostType::LEGAL_COSTS->value,
                'custom_cost_amount_in_euros' => 1_500.01,
            ],
            [
                'custom_cost_type' => CustomCostType::COURT_FEES->value,
                'custom_cost_amount_in_euros' => 750.01,
            ],
        ];

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::PETITION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRouteAsHtmx(
                RouteName::DEPARTMENTS_PETITIONS_CUSTOM_COSTS_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                ['custom_costs' => $customCosts],
            )
            ->assertOk();

        $this->assertDatabaseHas('custom_costs', [
            [
                'petition_id' => $petition->id,
                'custom_cost_type' => CustomCostType::LEGAL_COSTS->value,
                'custom_cost_amount_in_cents' => 150_001,
            ],
            [
                'petition_id' => $petition->id,
                'custom_cost_type' => CustomCostType::COURT_FEES->value,
                'custom_cost_amount_in_cents' => 75_001,
            ],
        ]);
    }

    public function testUpdateCustomCostsHasErrorsWithHtmx(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        $customCosts = [
            [
                'custom_cost_type' => CustomCostType::LEGAL_COSTS->value,
                'custom_cost_amount_in_euros' => '10_000_000_000',
            ],
        ];

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(
                RouteName::DEPARTMENTS_PETITIONS_CUSTOM_COSTS_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition->id,
                ],
                [
                    'hx-target' => $this->faker->word,
                    'custom_costs' => $customCosts,
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseMissing('custom_costs', [
            'petition_id' => $petition->id,
            'custom_cost_type' => CustomCostType::LEGAL_COSTS->value,
        ]);
    }

    public function testUpdateCustomCostsNoHtmx(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->recycle($petitionType)->create();

        Config::set('custom_cost.' . $petitionType->type->value, [
            CustomCostType::LEGAL_COSTS->value,
            CustomCostType::COURT_FEES->value,
        ]);

        $customCosts = [
            [
                'custom_cost_type' => CustomCostType::LEGAL_COSTS->value,
                'custom_cost_amount_in_euros' => 1_500.01,
            ],
            [
                'custom_cost_type' => CustomCostType::COURT_FEES->value,
                'custom_cost_amount_in_euros' => 750.01,
            ],
        ];

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_CUSTOM_COSTS_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                ['custom_costs' => $customCosts],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]);

        $this->assertDatabaseHas('custom_costs', [
            [
                'petition_id' => $petition->id,
                'custom_cost_type' => CustomCostType::LEGAL_COSTS->value,
                'custom_cost_amount_in_cents' => 150_001,
            ],
            [
                'petition_id' => $petition->id,
                'custom_cost_type' => CustomCostType::COURT_FEES->value,
                'custom_cost_amount_in_cents' => 75_001,
            ],
        ]);
    }
}
