<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\CustomCost;

use App\Actions\CustomCost\CustomCostUpdateAction;
use App\Enums\CustomCostType;
use App\Enums\TimelineType;
use App\Models\CustomCost;
use App\Models\Petition;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class CustomCostUpdateActionTest extends FeatureTestCase
{
    #[Test]
    public function itCreatesNewCustomCostsWhenNoneExist(): void
    {
        $petition = Petition::factory()->create();
        $user = User::factory()->create();

        $customCostData = [
            'custom_costs' => [
                [
                    'custom_cost_type' => CustomCostType::LEGAL_COSTS->value,
                    'custom_cost_amount_in_euros' => 1_000.00,
                ],
                [
                    'custom_cost_type' => CustomCostType::COURT_FEES->value,
                    'custom_cost_amount_in_euros' => 500.00,
                ],
            ],
        ];
        $action = $this->app->make(CustomCostUpdateAction::class);
        $action->execute($petition, $user, $customCostData);

        $this->assertCount(2, $petition->refresh()->customCosts);

        $this->assertDatabaseHas('custom_costs', [
            'petition_id' => $petition->id,
            'custom_cost_type' => CustomCostType::LEGAL_COSTS->value,
            'custom_cost_amount_in_cents' => 100_000,
        ]);

        $this->assertDatabaseHas('custom_costs', [
            'petition_id' => $petition->id,
            'custom_cost_type' => CustomCostType::COURT_FEES->value,
            'custom_cost_amount_in_cents' => 50_000,
        ]);

        $this->assertDatabaseHas('timeline_items', [
            'timelineable_id' => $petition->id,
            'user_id' => $user->id,
            'type' => TimelineType::CUSTOM_COST_UPDATED->value,
        ]);
    }

    #[Test]
    public function itUpdatesExistingCustomCosts(): void
    {
        $petition = Petition::factory()->create();
        $user = User::factory()->create();

        CustomCost::factory()->create([
            'petition_id' => $petition->id,
            'custom_cost_type' => CustomCostType::LEGAL_COSTS,
            'custom_cost_amount_in_cents' => 100_000, // 1000 euros
        ]);

        $customCostData = [
            'custom_costs' => [
                [
                    'custom_cost_type' => CustomCostType::OTHER->value,
                    'custom_cost_amount_in_euros' => 2_000.01, // Updated amount
                ],
            ],
        ];

        $action = $this->app->make(CustomCostUpdateAction::class);
        $action->execute($petition, $user, $customCostData);

        $this->assertCount(1, $petition->refresh()->customCosts);

        $this->assertDatabaseHas('custom_costs', [
            'petition_id' => $petition->id,
            'custom_cost_type' => CustomCostType::OTHER->value,
            'custom_cost_amount_in_cents' => 200_001,
        ]);

        $this->assertDatabaseHas('timeline_items', [
            'timelineable_id' => $petition->id,
            'user_id' => $user->id,
            'type' => TimelineType::CUSTOM_COST_UPDATED->value,
        ]);
    }

    #[Test]
    public function itRemovesCustomCostsNotInTheRequest(): void
    {
        $petition = Petition::factory()->create();
        $user = User::factory()->create();

        CustomCost::factory()->create([
            'petition_id' => $petition->id,
            'custom_cost_type' => CustomCostType::LEGAL_COSTS,
        ]);

        $courtFees = CustomCost::factory()->create([
            'petition_id' => $petition->id,
            'custom_cost_type' => CustomCostType::COURT_FEES,
        ]);

        $customCostData = [
            'custom_costs' => [
                [
                    'custom_cost_type' => CustomCostType::LEGAL_COSTS->value,
                    'custom_cost_amount_in_euros' => 1_000,
                ],
            ],
        ];

        $action = $this->app->make(CustomCostUpdateAction::class);
        $action->execute($petition, $user, $customCostData);

        $this->assertCount(1, $petition->refresh()->customCosts);

        $this->assertDatabaseHas('custom_costs', [
            'petition_id' => $petition->id,
            'custom_cost_type' => CustomCostType::LEGAL_COSTS->value,
        ]);

        $this->assertDatabaseMissing('custom_costs', [
            'id' => $courtFees->id,
        ]);

        $this->assertDatabaseHas('timeline_items', [
            'timelineable_id' => $petition->id,
            'user_id' => $user->id,
            'type' => TimelineType::CUSTOM_COST_UPDATED->value,
        ]);
    }

    #[Test]
    public function itSkipsCreatingCostsWithZeroAmount(): void
    {
        $petition = Petition::factory()->create();
        $user = User::factory()->create();

        $customCostData = [
            'custom_costs' => [
                [
                    'custom_cost_type' => CustomCostType::LEGAL_COSTS->value,
                    'custom_cost_amount_in_euros' => 1_000.01,
                ],
                [
                    'custom_cost_type' => CustomCostType::COURT_FEES->value,
                    'custom_cost_amount_in_euros' => 0.00,
                ],
            ],
        ];

        $action = $this->app->make(CustomCostUpdateAction::class);
        $action->execute($petition, $user, $customCostData);

        $this->assertCount(1, $petition->refresh()->customCosts);

        $this->assertDatabaseHas('custom_costs', [
            'petition_id' => $petition->id,
            'custom_cost_type' => CustomCostType::LEGAL_COSTS->value,
            'custom_cost_amount_in_cents' => 100_001,
        ]);

        $this->assertDatabaseMissing('custom_costs', [
            'petition_id' => $petition->id,
            'custom_cost_type' => CustomCostType::COURT_FEES->value,
        ]);
    }
}
