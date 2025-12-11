<?php

declare(strict_types=1);

namespace App\Actions\Decision;

use App\Enums\TimelineType;
use App\Models\Decision;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Throwable;

readonly class DecisionUnarchiveAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function execute(Decision $decision, User $user): void
    {
        if ($decision->archived_at === null) {
            return;
        }

        $this->databaseManager->transaction(static function () use ($decision, $user): void {
            $decision->archived_at = null;
            $decision->save();

            $decision->timelineItems()->create([
                'type' => TimelineType::DECISION_UNARCHIVED,
                'user_id' => $user->id,
                'data' => null,
            ]);
        });
    }
}
