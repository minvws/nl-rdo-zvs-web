<?php

declare(strict_types=1);

namespace App\View\Components\Petition\PetitionTerms;

use App\Collections\PetitionTermCollection;
use App\Models\Petition;
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
        public Petition $petition,
        public PetitionTermCollection $petitionTerms,
        private readonly Factory $view,
    ) {
    }

    public function render(): ?View
    {
        if ($this->petitionTerms->isEmpty() || $this->petitionTerms->hasFirstTerm() === false) {
            // @codeCoverageIgnoreStart
            return $this->view->make('petition.petition-terms.summary-empty');
            // @codeCoverageIgnoreEnd
        }

        return $this->view->make('petition.petition-terms.summary')->with([
            'current_terms' => $this->petition->isTermEngineConverted() ? null : $this->petitionTerms->currentTerms(CalendarDate::today()),
            'total_days_of_suspensions' => $this->petition->total_days_suspended,
            'sum_of_penalties_per_date' => $this->petition->legacy_term_penalty_today + $this->petition->igs_penalty_today + $this->petition->bnt_penalty_today,
            'totalPenalty' => $this->petition->legacy_term_penalty_maximum + $this->petition->igs_penalty_maximum + $this->petition->bnt_penalty_maximum,
            'penaltyToDate' => $this->petition->legacy_term_forfeited + $this->petition->igs_forfeited + $this->petition->bnt_forfeited,
        ]);
    }
}
