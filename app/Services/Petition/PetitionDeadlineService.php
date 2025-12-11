<?php

declare(strict_types=1);

namespace App\Services\Petition;

use App\Models\Petition;
use App\ValueObjects\CalendarDate;
use Webmozart\Assert\Assert;

class PetitionDeadlineService
{
    public function calculateDeadline(Petition $petition): ?CalendarDate
    {
        return match (true) {
            $this->hasDraftTerm($petition) => null,
            $this->hasDeliverables($petition) => $this->getDeliverablesDeadline($petition),
            default => $this->getTermsDeadline($petition),
        };
    }

    private function hasDraftTerm(Petition $petition): bool
    {
        return $petition->draftTerm()->exists();
    }

    private function hasDeliverables(Petition $petition): bool
    {
        return $petition->petitionDeliverables()->exists();
    }

    private function getTermsDeadline(Petition $petition): CalendarDate
    {
        if ($petition->petitionTerms->isEmpty()) {
            return $petition->date_of_entry;
        }

        $deadline = $petition->petitionTerms->deadline();

        return $deadline ?? $petition->date_of_entry;
    }

    private function getDeliverablesDeadline(Petition $petition): CalendarDate
    {
        $latestDeadlineFromDeliverables = $petition->petitionDeliverables
            ->max('deadline_at');

        Assert::nullOrIsInstanceOf($latestDeadlineFromDeliverables, CalendarDate::class);

        return $latestDeadlineFromDeliverables ?? $petition->date_of_entry;
    }
}
