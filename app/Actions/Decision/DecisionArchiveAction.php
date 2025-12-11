<?php

declare(strict_types=1);

namespace App\Actions\Decision;

use App\Enums\TimelineType;
use App\Models\Decision;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Throwable;

readonly class DecisionArchiveAction
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
        if ($decision->archived_at !== null) {
            return;
        }

        $this->databaseManager->transaction(static function () use ($decision, $user): void {
            $decision->update(['archived_at' => CarbonImmutable::now()]);

            $decision->timelineItems()->create([
                'type' => TimelineType::DECISION_ARCHIVED,
                'user_id' => $user->id,
                'data' => null,
            ]);
        });
    }
}
