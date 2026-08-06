<?php

declare(strict_types=1);

namespace App\Services\Petition;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;
use Webmozart\Assert\Assert;

use function array_merge;
use function array_unique;
use function in_array;

class PetitionParticularityCollector
{
    public const string LABEL_NOTICE_OF_DEFAULT = 'IGS';
    public const string LABEL_SUSPENSION = 'Opsch';
    public const string LABEL_ADJOURNMENT = 'Aanh';

    private const array ADJOURNMENT_TERM_TYPES = [
        TermType::SPECIFIED_ADJOURNMENT,
        TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT,
        TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL,
    ];

    /**
     * @return array<string>
     */
    public function collectParticularities(Petition $petition): array
    {
        $today = CalendarDate::today();
        $labels = [];

        if ($this->hasActiveNoticeOfDefault($petition)) {
            $labels[] = self::LABEL_NOTICE_OF_DEFAULT;
        }

        if ($this->hasActiveSuspension($petition, $today)) {
            $labels[] = self::LABEL_SUSPENSION;
        }

        if ($this->hasActiveAdjournment($petition, $today)) {
            $labels[] = self::LABEL_ADJOURNMENT;
        }

        return array_unique(array_merge($this->getLabelsFromRelatedPetitions($petition), $labels));
    }

    private function hasActiveNoticeOfDefault(Petition $petition): bool
    {
        if ($petition->isTermEngineConverted()) {
            $receivedCount = $petition->petitionEvents
                ->filter(static fn($e): bool => $e->type === PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED)
                ->count();
            $withdrawnCount = $petition->petitionEvents
                ->filter(static fn($e): bool => $e->type === PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN)
                ->count();

            return $receivedCount > $withdrawnCount;
        }

        return $petition->petitionTerms->hasNoticeOfDefault();
    }

    private function hasActiveSuspension(Petition $petition, CalendarDate $today): bool
    {
        return $petition->petitionTerms
            ->filter(static fn(PetitionTerm $term): bool => $term->type === TermType::SUSPENSION)
            ->currentTerms($today)
            ->isNotEmpty();
    }

    private function hasActiveAdjournment(Petition $petition, CalendarDate $today): bool
    {
        $hasAdjournmentTerm = $petition->petitionTerms
            ->filter(static fn(PetitionTerm $term): bool => in_array($term->type, self::ADJOURNMENT_TERM_TYPES, true))
            ->currentTerms($today)
            ->isNotEmpty();

        $hasFutureDraftTerm = $petition->draftTerm?->start_date > $today;

        return $hasAdjournmentTerm || $hasFutureDraftTerm;
    }

    /**
     * @return array<array-key, string>
     */
    private function getLabelsFromRelatedPetitions(Petition $petition): array
    {
        $labels = $petition->relatedPetitions
            ->pluck('petitionType.particularity_label')
            ->filter()
            ->unique()
            ->toArray();

        Assert::allString($labels);

        return $labels;
    }
}
