<?php

declare(strict_types=1);

namespace App\Actions\Decision;

use App\Enums\TimelineType;
use App\Models\Decision;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;

readonly class DecisionUpdateAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(Decision $decision, User $user, array $attributes): void
    {
        $this->databaseManager->transaction(static function () use ($decision, $user, $attributes): void {
            $decision->update($attributes);
            $decision->refresh();
            $decision->timelineItems()->create([
                'type' => TimelineType::DECISION_UPDATED,
                'user_id' => $user->id,
                'data' => new ArrayObject([
                    'name' => $decision->name,
                    'date' => $decision->date?->format('Y-m-d'),
                    'reference' => $decision->reference,
                ]),
            ]);
        });
    }
}
