<?php

declare(strict_types=1);

namespace App\View\Components\Petition\PetitionTerms;

use App\Collections\PetitionTermCollection;
use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Summary extends Component
{
    /**
     * @param PetitionTermCollection<int, PetitionTerm> $petitionTerms
     */
    public function __construct(
        public PetitionTermCollection $petitionTerms,
        private readonly Factory $view,
    ) {
    }

    public function render(): ?View
    {
        if ($this->petitionTerms->isEmpty() || $this->petitionTerms->hasFirstTerm() === false) {
            return $this->view->make('petition.petition-terms.summary-empty');
        }

        return $this->view->make('petition.petition-terms.summary')->with([
            'current_terms' => $this->petitionTerms->currentTerms(CalendarDate::today()),
            'total_days_of_suspensions' => $this->petitionTerms->totalDaysOfSuspensions(),
            'sum_of_penalties_per_date' => $this->petitionTerms->sumOfPenaltiesPerDate(CalendarDate::today()),
            'totalPenalty' => $this->petitionTerms->totalPenalty(),
            'penaltyToDate' => $this->petitionTerms->penaltyToDate(CalendarDate::today()),
        ]);
    }
}
