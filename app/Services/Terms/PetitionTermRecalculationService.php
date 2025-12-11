<?php

declare(strict_types=1);

namespace App\Services\Terms;

use App\Collections\PetitionTermCollection;
use App\Models\PetitionTerm;
use App\Services\LegalTermDeadlineCalculator;
use App\ValueObjects\CalendarDate;

class PetitionTermRecalculationService
{
    private PetitionTermCollection $suspensions;
    private PetitionTermCollection $petitionTermsToArrange;

    public function __construct(
        private readonly LegalTermDeadlineCalculator $deadlineCalculator,
        private readonly SuspensionApplier $suspensionApplier,
    ) {
    }

    public function recalculate(PetitionTermCollection $petitionTerms, CalendarDate $defaultStartDate): void
    {
        $petitionTerms->each(static function (PetitionTerm $petitionTerm): void {
            $petitionTerm->end_date = TermDateCalculator::calculateEndDate($petitionTerm->start_date, $petitionTerm->duration_in_days);
        });

        $this->suspensions = $petitionTerms->suspensions();
        $this->petitionTermsToArrange = $petitionTerms->suspendables();

        $firstTerm = $petitionTerms->getFirstTerm();
        $secondTerm = $petitionTerms->getSecondTerm();
        $thirdTerm = $petitionTerms->getThirdTerm();
        $committeeHearing = $petitionTerms->getCommitteeHearingTerm();

        $this->reArrangeNoticeOfDefaultWithItsPenalties($petitionTerms);
        $this->applyLegalTermOnObjectionPeriod($petitionTerms);
        $this->adjustFirstTermBasedOnObjectionPeriod($petitionTerms);

        if ($firstTerm instanceof PetitionTerm) {
            $this->calculateTerm($firstTerm);
        }

        if ($committeeHearing instanceof PetitionTerm && $firstTerm instanceof PetitionTerm) {
            $committeeHearing->start_date = $firstTerm->end_date->addDay();
            $this->calculateTerm($committeeHearing);
        }

        if ($secondTerm instanceof PetitionTerm) {
            $secondTerm->start_date = $committeeHearing instanceof PetitionTerm
                ? $committeeHearing->end_date->addDay()
                : $firstTerm?->end_date->addDay() ?? $defaultStartDate;
            $this->calculateTerm($secondTerm);
        }

        if (!$thirdTerm instanceof PetitionTerm || !$secondTerm instanceof PetitionTerm) {
            return;
        }

        $thirdTerm->start_date = $secondTerm->end_date->addDay();
        $this->calculateTerm($thirdTerm);
    }

    private function calculateTerm(PetitionTerm $petitionTerm): void
    {
        // Calculate basic end date based on start date and duration_in_days
        $petitionTerm->end_date = TermDateCalculator::calculateEndDate($petitionTerm->start_date, $petitionTerm->duration_in_days);

        // Apply any suspensions that affect this term
        if ($this->suspensions->isNotEmpty()) {
            $this->suspensionApplier->applySuspensions($petitionTerm, $this->suspensions);
        }

        // Apply legal term deadline calculations only if this is the last term
        if ($this->petitionTermsToArrange->isLastSuspendable($petitionTerm)) {
            $petitionTerm->end_date = $this->deadlineCalculator->calculate($petitionTerm->end_date);
        }
    }

    private function reArrangeNoticeOfDefaultWithItsPenalties(PetitionTermCollection $petitionTermCollection): void
    {
        $parentPetitionTerm = $petitionTermCollection->getNoticeOfDefault();

        if (!$parentPetitionTerm instanceof PetitionTerm) {
            return;
        }

        $parentPetitionTerm->end_date = $this->deadlineCalculator->calculate($parentPetitionTerm->end_date);

        $childPetitionTerm = $petitionTermCollection->getChildTerm($parentPetitionTerm);

        while ($childPetitionTerm instanceof PetitionTerm) {
            $childPetitionTerm->start_date = $parentPetitionTerm->end_date->addDay();
            $childPetitionTerm->end_date = TermDateCalculator::calculateEndDate(
                $childPetitionTerm->start_date,
                $childPetitionTerm->duration_in_days,
            );
            $parentPetitionTerm = $childPetitionTerm;
            $childPetitionTerm = $petitionTermCollection->getChildTerm($parentPetitionTerm);
        }
    }

    private function applyLegalTermOnObjectionPeriod(PetitionTermCollection $petitionTermCollection): void
    {
        $term = $petitionTermCollection->getObjectionPeriod();

        if (!$term instanceof PetitionTerm) {
            return;
        }

        $term->end_date = $this->deadlineCalculator->calculate($term->end_date);
    }

    private function adjustFirstTermBasedOnObjectionPeriod(PetitionTermCollection $petitionTermCollection): void
    {
        $objectionPeriod = $petitionTermCollection->getObjectionPeriod();
        $firstTerm = $petitionTermCollection->getFirstTerm();

        if (!$objectionPeriod instanceof PetitionTerm || !$firstTerm instanceof PetitionTerm) {
            return;
        }

        $suggestedFirstTermStartDate = $objectionPeriod->end_date->addDay();

        if ($suggestedFirstTermStartDate->greaterThan($firstTerm->start_date)) {
            $firstTerm->start_date = $suggestedFirstTermStartDate;
        }
    }
}
