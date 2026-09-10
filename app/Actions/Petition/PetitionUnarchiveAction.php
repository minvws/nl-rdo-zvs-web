<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Actions\PetitionEvent\Contracts\UpdatePetitionTotalsFromEventsActionInterface;
use App\Actions\PetitionEvent\Contracts\UpdatePetitionTotalsFromTermsActionInterface;
use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Throwable;

readonly class PetitionUnarchiveAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private UpdatePetitionTotalsFromEventsActionInterface $updatePetitionTotalsFromEventsAction,
        private UpdatePetitionTotalsFromTermsActionInterface $updatePetitionTotalsFromTermsAction,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function execute(Petition $petition, User $user): void
    {
        if ($petition->archived_at === null) {
            return;
        }

        $this->databaseManager->transaction(function () use ($petition, $user): void {
            $petition->archived_at = null;
            $petition->save();

            $petition->timelineItems()->create([
                'type' => TimelineType::PETITION_UNARCHIVED,
                'user_id' => $user->id,
                'data' => null,
            ]);

            $this->updatePetitionTotalsFromEventsAction->execute($petition);
            $this->updatePetitionTotalsFromTermsAction->execute($petition);
        });
    }
}
