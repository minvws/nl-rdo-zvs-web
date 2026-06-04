<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\TimelineType;
use App\Models\Decision;
use App\Models\DecisionPetition;
use App\Models\Petition;
use App\Models\User;
use ArrayObject;
use Illuminate\Database\DatabaseManager;
use Throwable;

readonly class SetFinalDecisionAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @param array{final_decision_id?: string|null} $attributes
     *
     * @throws Throwable
     */
    public function execute(Petition $petition, array $attributes, User $user): void
    {
        /** @var string|null $finalDecisionId */
        $finalDecisionId = $attributes['final_decision_id'] ?? null;
        $finalDecision = $finalDecisionId !== null
            ? Decision::query()->findOrFail($finalDecisionId)
            : null;

        $this->databaseManager->transaction(static function () use ($petition, $finalDecision, $user): void {
            // Reset all decisions for this petition to not-final
            DecisionPetition::query()
                ->where('petition_id', $petition->id)
                ->update(['is_final' => false]);

            // Set the selected decision as final
            if ($finalDecision instanceof Decision) {
                DecisionPetition::query()
                    ->where('petition_id', $petition->id)
                    ->where('decision_id', $finalDecision->id)
                    ->update(['is_final' => true]);
            }

            // Create timeline entry on the Petition
            $petition->timelineItems()->create([
                'user_id' => $user->id,
                'type' => TimelineType::FINAL_DECISION_SET,
                'data' => new ArrayObject([
                    'decision_name' => $finalDecision instanceof Decision ? $finalDecision->name : null,
                ]),
            ]);
        });
    }
}
