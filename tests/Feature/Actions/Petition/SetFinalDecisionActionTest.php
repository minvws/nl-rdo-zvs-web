<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition;

use App\Actions\Petition\SetFinalDecisionAction;
use App\Enums\TimelineType;
use App\Models\Decision;
use App\Models\DecisionPetition;
use App\Models\Petition;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class SetFinalDecisionActionTest extends FeatureTestCase
{
    #[Test]
    public function testItSetsADecisionAsFinal(): void
    {
        $petition = Petition::factory()->create();
        $decision = Decision::factory()->create();
        $petition->decisions()->attach($decision);

        $attributes = [];
        $attributes['final_decision_id'] = $decision->id->toString();

        $user = User::factory()->create();

        /** @var SetFinalDecisionAction $action */
        $action = $this->app->make(SetFinalDecisionAction::class);
        $action->execute($petition, $attributes, $user);

        $this->assertDatabaseHas('decision_petition', [
            'petition_id' => $petition->id,
            'decision_id' => $decision->id,
            'is_final' => true,
        ]);
    }

    #[Test]
    public function testItResetsOtherDecisionsToNotFinalWhenSettingANewFinal(): void
    {
        $petition = Petition::factory()->create();
        $decisionA = Decision::factory()->create();
        $decisionB = Decision::factory()->create();
        $petition->decisions()->attach($decisionA);
        $petition->decisions()->attach($decisionB);

        $attributes = [];
        $attributes['final_decision_id'] = $decisionB->id->toString();

        // Manually mark decisionA as final
        DecisionPetition::query()
            ->where('petition_id', $petition->id)
            ->where('decision_id', $decisionA->id)
            ->update(['is_final' => true]);

        $user = User::factory()->create();

        /** @var SetFinalDecisionAction $action */
        $action = $this->app->make(SetFinalDecisionAction::class);
        $action->execute($petition, $attributes, $user);

        $this->assertDatabaseHas('decision_petition', [
            [ 'petition_id' => $petition->id, 'decision_id' => $decisionB->id, 'is_final' => true ],
            [ 'petition_id' => $petition->id, 'decision_id' => $decisionA->id, 'is_final' => false ],
        ]);
    }

    #[Test]
    public function testItClearsAllFinalsWhenPassingNull(): void
    {
        $petition = Petition::factory()->create();
        $decision = Decision::factory()->create();
        $petition->decisions()->attach($decision);

        $attributes = [];
        $attributes['final_decision_id'] = null;

        // Manually mark as final
        DecisionPetition::query()
            ->where('petition_id', $petition->id)
            ->where('decision_id', $decision->id)
            ->update(['is_final' => true]);

        $user = User::factory()->create();

        /** @var SetFinalDecisionAction $action */
        $action = $this->app->make(SetFinalDecisionAction::class);
        $action->execute($petition, $attributes, $user);

        $this->assertDatabaseHas('decision_petition', [
            'petition_id' => $petition->id,
            'decision_id' => $decision->id,
            'is_final' => false,
        ]);
    }

    #[Test]
    public function testItCreatesATimelineEntryWhenSettingADecision(): void
    {
        $petition = Petition::factory()->create();
        $decision = Decision::factory()->create();
        $petition->decisions()->attach($decision);

        $attributes = [];
        $attributes['final_decision_id'] = $decision->id->toString();
        $user = User::factory()->create();

        /** @var SetFinalDecisionAction $action */
        $action = $this->app->make(SetFinalDecisionAction::class);
        $action->execute($petition, $attributes, $user);

        $this->assertDatabaseHas('timeline_items', [
            'timelineable_type' => 'petition',
            'timelineable_id' => $petition->id,
            'user_id' => $user->id,
            'type' => TimelineType::FINAL_DECISION_SET->value,
        ]);
    }

    #[Test]
    public function testItCreatesATimelineEntryWhenClearingTheFinalDecision(): void
    {
        $petition = Petition::factory()->create();
        $user = User::factory()->create();

        $attributes = [];
        $attributes['final_decision_id'] = null;

        /** @var SetFinalDecisionAction $action */
        $action = $this->app->make(SetFinalDecisionAction::class);
        $action->execute($petition, $attributes, $user);

        $this->assertDatabaseHas('timeline_items', [
            'timelineable_type' => 'petition',
            'timelineable_id' => $petition->id,
            'user_id' => $user->id,
            'type' => TimelineType::FINAL_DECISION_SET->value,
        ]);
    }
}
