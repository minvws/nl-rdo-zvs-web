<?php

declare(strict_types=1);

namespace App\Actions\PetitionEvent;

use App\Actions\PetitionEvent\Contracts\UpdatePetitionTotalsFromTermsActionInterface;
use App\Models\Petition;
use App\ValueObjects\CalendarDate;

readonly class UpdatePetitionTotalsFromTermsAction implements UpdatePetitionTotalsFromTermsActionInterface
{
    public function execute(Petition $petition): void
    {
        if ($petition->isTermEngineConverted()) {
            return;
        }

        $updates = [
            'deadline_at' => $this->calculateDeadlineAt($petition),
            'total_days_suspended' => $this->calculateTotalDaysSuspended($petition),

            'igs_penalty_today' => 0,
            'igs_forfeited' => 0,
            'igs_penalty_maximum' => 0,

            'bnt_penalty_today' => 0,
            'bnt_forfeited' => 0,
            'bnt_penalty_maximum' => 0,

            'legacy_term_penalty_today' => $this->calculatePenaltyToday($petition),
            'legacy_term_forfeited' => $this->calculateForfeited($petition),
            'legacy_term_penalty_maximum' => $this->calculatePenaltyMaximum($petition),
        ];

        $petition->update($updates);
    }

    private function calculateDeadlineAt(Petition $petition): ?CalendarDate
    {
        return $petition->petitionTerms->deadline();
    }

    private function calculateTotalDaysSuspended(Petition $petition): int
    {
        return $petition->petitionTerms->totalDaysOfSuspensions();
    }

    private function calculatePenaltyToday(Petition $petition): int
    {
        return $petition->petitionTerms->sumOfPenaltiesPerDate(CalendarDate::today());
    }

    private function calculateForfeited(Petition $petition): int
    {
        return $petition->petitionTerms->penaltyToDate(CalendarDate::today());
    }

    private function calculatePenaltyMaximum(Petition $petition): int
    {
        return $petition->petitionTerms->totalPenalty();
    }
}
