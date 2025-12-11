<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Models\Petition;
use App\Services\Petition\PetitionDeadlineService;
use App\Services\Terms\PetitionTermRecalculationService;

readonly class PetitionTermsUpdateAction
{
    public function __construct(
        private PetitionTermRecalculationService $recalculationService,
        private PetitionDeadlineService $petitionDeadlineService,
    ) {
    }

    public function execute(Petition $petition): void
    {
        $this->recalculationService->recalculate($petition->petitionTerms, $petition->date_of_entry);
        $petition->petitionTerms()->saveMany($petition->petitionTerms);

        $petition->deadline_at = $this->petitionDeadlineService->calculateDeadline($petition);
        $petition->save();
    }
}
