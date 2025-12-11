<?php

declare(strict_types=1);

namespace App\Services\Terms;

use App\Collections\PetitionTermCollection;
use App\Enums\TermType;
use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;

class SuspensionApplier
{
    public function applySuspensions(PetitionTerm $term, PetitionTermCollection $petitionTerms): void
    {
        if (!$term->type->isSuspendable()) {
            return;
        }

        foreach ($petitionTerms->suspensions() as $suspension) {
            $suspension->end_date = $this->endDate($suspension);

            if (!$this->isSuspensionApplicable($suspension, $term)) {
                continue;
            }

            if ($this->isSuspensionAtBeginningOrBeforeTerm($suspension, $term)) {
                $daysOverlap = $term->start_date->diffInDays($suspension->end_date);

                $term->start_date = $suspension->start_date->addDays($suspension->duration_in_days);
                $term->end_date = $term->end_date->addDays($daysOverlap + 1);

                continue;
            }

            $term->end_date = $term->end_date->addDays($suspension->duration_in_days);
        }
    }

    private function isSuspensionApplicable(PetitionTerm $suspension, PetitionTerm $term): bool
    {
        return match ($term->type) {
            TermType::FIRST => $this->isSuspensionApplicableOnFirstTerm($suspension, $term),
            default => $suspension->start_date <= $term->end_date && $suspension->start_date >= $term->start_date,
        };
    }

    private function isSuspensionAtBeginningOrBeforeTerm(PetitionTerm $suspension, PetitionTerm $term): bool
    {
        if ($suspension->start_date > $term->start_date) {
            return false;
        }

        return $suspension->start_date->addDays($suspension->duration_in_days) >= $term->start_date;
    }

    private function isSuspensionApplicableOnFirstTerm(PetitionTerm $suspension, PetitionTerm $term): bool
    {
        if ($term->start_date <= $suspension->end_date && $term->start_date >= $suspension->start_date) {
            return true;
        }

        return $suspension->start_date >= $term->start_date && $suspension->start_date <= $term->end_date;
    }

    private function endDate(PetitionTerm $term): CalendarDate
    {
        return TermDateCalculator::calculateEndDate($term->start_date, $term->duration_in_days);
    }
}
