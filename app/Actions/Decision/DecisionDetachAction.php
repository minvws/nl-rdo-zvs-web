<?php

declare(strict_types=1);

namespace App\Actions\Decision;

use App\Enums\Occurrence;
use App\Enums\TimelineType;
use App\Models\Decision;
use App\Models\DecisionPetition;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Throwable;

readonly class DecisionDetachAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function execute(Decision $decision, Petition $petition, User $user): void
    {
        $this->databaseManager->transaction(static function () use ($decision, $petition, $user): void {
            DecisionPetition::query()->where([
                'decision_id' => $decision->id,
                'petition_id' => $petition->id,
            ])->delete();

            $petition->timelineItems()->create([
                'user_id' => $user->id,
                'type' => TimelineType::REFERENCED_OCCURRENCE,
                'data' => new ArrayObject([
                    'type' => Occurrence::DECISION_TYPE->value,
                    'action' => Occurrence::DETACH_ACTION->value,
                    'subject' => $decision->name,
                ]),
            ]);

            $decision->timelineItems()->create([
                'user_id' => $user->id,
                'type' => TimelineType::REFERENCED_OCCURRENCE,
                'data' => new ArrayObject([
                    'type' => Occurrence::PETITION_TYPE->value,
                    'action' => Occurrence::DETACH_ACTION->value,
                    'subject' => $petition->number,
                ]),
            ]);
        });
    }
}
