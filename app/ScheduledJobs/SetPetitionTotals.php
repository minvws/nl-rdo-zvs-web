<?php

declare(strict_types=1);

namespace App\ScheduledJobs;

use App\Actions\PetitionEvent\Contracts\UpdatePetitionTotalsFromEventsActionInterface;
use App\Actions\PetitionEvent\Contracts\UpdatePetitionTotalsFromTermsActionInterface;
use App\Models\Petition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

use function sprintf;

class SetPetitionTotals
{
    private const int BATCH_SIZE = 500;

    public function __construct(
        private readonly UpdatePetitionTotalsFromEventsActionInterface $updatePetitionTotalsFromEventsAction,
        private readonly UpdatePetitionTotalsFromTermsActionInterface $updatePetitionTotalsFromTermsAction,
    ) {
    }

    public function __invoke(): void
    {
        $this->setTotalsOnConvertedPetitions();
        $this->setTotalsOnLegacyPetitions();
    }

    public function setTotalsOnConvertedPetitions(): void
    {
        Petition::query()
            ->whereHas('petitionEvents')
            ->notArchived()
            ->chunk(self::BATCH_SIZE, function (Collection $petitions): void {
                foreach ($petitions as $petition) {
                    try {
                        $this->updatePetitionTotalsFromEventsAction->execute($petition);
                    } catch (Throwable) {
                        Log::error(
                            message: sprintf('Failed to update petition totals from events for petition ID: %s', $petition->id),
                        );
                        continue;
                    }
                }
            });
    }

    private function setTotalsOnLegacyPetitions(): void
    {
        Petition::query()
            ->whereHas('petitionTerms')
            ->whereDoesntHave('petitionEvents')
            ->notArchived()
            ->chunk(self::BATCH_SIZE, function (Collection $petitions): void {
                foreach ($petitions as $petition) {
                    try {
                        $this->updatePetitionTotalsFromTermsAction->execute($petition);
                    } catch (Throwable) {
                        Log::error(
                            message: sprintf('Failed to update petition totals from terms for legacy petition ID: %s', $petition->id),
                        );
                        continue;
                    }
                }
            });
    }
}
