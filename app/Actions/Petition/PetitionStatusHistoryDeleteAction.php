<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\PetitionStatusHistory;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Throwable;

readonly class PetitionStatusHistoryDeleteAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function execute(Petition $petition, User $user, PetitionStatusHistory $historyEntry): void
    {
        $this->databaseManager->transaction(function () use ($petition, $user, $historyEntry): void {
            $latestEntry = $this->getLatestPetitionStatusHistory($petition, $historyEntry);

            if (!$latestEntry instanceof PetitionStatusHistory) {
                return;
            }

            $petition->update(['petition_status_id' => $latestEntry->petition_status_id]);

            $historyEntry->delete();

            $petition->timelineItems()->create([
                'type' => TimelineType::STATUS_OCCURRENCE,
                'user_id' => $user->id,
                'data' => new ArrayObject([
                    'previous_status' => $historyEntry->petitionStatus->status,
                    'current_status' => $latestEntry->petitionStatus->status,
                    'date' => $latestEntry->date->toDateString(),
                    'comment' => 'Status verwijderd',
                ]),
            ]);
        });
    }

    private function getLatestPetitionStatusHistory(Petition $petition, PetitionStatusHistory $historyEntry): ?PetitionStatusHistory
    {
        return PetitionStatusHistory::query()
            ->where('petition_id', $petition->id)
            ->where('id', '!=', $historyEntry->id)
            ->orderBy('date', 'desc')->latest()
            ->first();
    }
}
