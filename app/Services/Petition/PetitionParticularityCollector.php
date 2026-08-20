<?php

declare(strict_types=1);

namespace App\Services\Petition;

use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

use function array_merge;
use function array_unique;
use function in_array;

class PetitionParticularityCollector
{
    public const string LABEL_NOTICE_OF_DEFAULT = 'IGS';
    public const string LABEL_SUSPENSION = 'Opschorting';
    public const string LABEL_ADJOURNMENT = 'Aanhouding';
    public const string LABEL_APPEAL_NOT_TIMELY = 'BNT';
    public const string LABEL_ADJOURNMENT_LETTER = 'Verdaging';

    private const array ADJOURNMENT_TERM_TYPES = [
        TermType::SPECIFIED_ADJOURNMENT,
        TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT,
        TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL,
    ];

    public const array SUSPENSION_TYPES = [
        SuspensionType::SUSPENSION,
        SuspensionType::SPECIFICATION,
        SuspensionType::CONSULTATION,
    ];

    /**
     * @return array<string>
     */
    public function collectParticularities(Petition $petition): array
    {
        $today = CalendarDate::today();

        $labels = $petition->isTermEngineConverted()
            ? $this->collectLabelsFromEvents($petition, $today)
            : $this->collectLabelsFromTerms($petition, $today);

        return array_unique(array_merge($this->getLabelsFromRelatedPetitions($petition), $labels));
    }

    /**
     * @return array<string>
     */
    private function collectLabelsFromTerms(Petition $petition, CalendarDate $today): array
    {
        $labels = [];

        if ($petition->petitionTerms->hasNoticeOfDefault()) {
            $labels[] = self::LABEL_NOTICE_OF_DEFAULT;
        }

        if ($this->hasCurrentTermOfType($petition, [TermType::SUSPENSION], $today)) {
            $labels[] = self::LABEL_SUSPENSION;
        }

        if ($this->hasCurrentTermOfType($petition, self::ADJOURNMENT_TERM_TYPES, $today) || $this->hasFutureDraftTerm($petition, $today)) {
            $labels[] = self::LABEL_ADJOURNMENT;
        }

        return $labels;
    }

    /**
     * @return array<string>
     */
    private function collectLabelsFromEvents(Petition $petition, CalendarDate $today): array
    {
        $events = $petition->petitionEvents;
        $labels = [];

        if ($this->hasActiveNoticeOfDefaultEvent($events)) {
            $labels[] = self::LABEL_NOTICE_OF_DEFAULT;
        }

        if ($this->hasActiveSuspensionLetter($events, self::SUSPENSION_TYPES, $today)) {
            $labels[] = self::LABEL_SUSPENSION;
        }

        if ($this->hasActiveAdjournmentEvent($events, $today) || $this->hasFutureDraftTerm($petition, $today)) {
            $labels[] = self::LABEL_ADJOURNMENT;
        }

        if ($this->hasRunningAppealNotTimelyTerm($events, $today)) {
            $labels[] = self::LABEL_APPEAL_NOT_TIMELY;
        }

        if ($this->hasEventOfType($events, PetitionEventType::ADJOURNMENT)) {
            $labels[] = self::LABEL_ADJOURNMENT_LETTER;
        }

        return $labels;
    }

    /**
     * @param array<TermType> $types
     */
    private function hasCurrentTermOfType(Petition $petition, array $types, CalendarDate $today): bool
    {
        return $petition->petitionTerms
            ->filter(static fn(PetitionTerm $term): bool => in_array($term->type, $types, true))
            ->currentTerms($today)
            ->isNotEmpty();
    }

    private function hasFutureDraftTerm(Petition $petition, CalendarDate $today): bool
    {
        return $petition->draftTerm?->start_date > $today;
    }

    /**
     * @param Collection<int, PetitionEvent> $events
     */
    private function hasActiveNoticeOfDefaultEvent(Collection $events): bool
    {
        $receivedCount = $events
            ->filter(static fn(PetitionEvent $e): bool => $e->type === PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED)
            ->count();
        $withdrawnCount = $events
            ->filter(static fn(PetitionEvent $e): bool => $e->type === PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN)
            ->count();

        return $receivedCount > $withdrawnCount;
    }

    /**
     * A suspension runs from the day after the letter was sent until either the day before the
     * registered end, or the last day of the duration stated in the letter.
     *
     * @param Collection<int, PetitionEvent> $events
     * @param array<SuspensionType> $suspensionTypes
     */
    private function hasActiveSuspensionLetter(Collection $events, array $suspensionTypes, CalendarDate $today): bool
    {
        $ends = $this->sortedByDate($events, PetitionEventType::SUSPENSION_END);

        return $this->sortedByDate($events, PetitionEventType::LETTER_OF_SUSPENSION_SENT)
            ->filter(
                static fn(PetitionEvent $event): bool => in_array($event->suspension_type, $suspensionTypes, true),
            )
            ->contains(static function (PetitionEvent $start) use ($ends, $today): bool {
                $firstDay = $start->date->addDay();

                $end = $ends->first(
                    static fn(PetitionEvent $end): bool => $end->date->greaterThanOrEqualTo($firstDay),
                );

                $lastDay = $end instanceof PetitionEvent
                    ? $end->date->subDay()
                    : $firstDay->addDays(($start->duration ?? 0) - 1);

                return $today->greaterThanOrEqualTo($firstDay) && $today->lessThanOrEqualTo($lastDay);
            });
    }

    /**
     * @param Collection<int, PetitionEvent> $events
     */
    private function hasActiveAdjournmentEvent(Collection $events, CalendarDate $today): bool
    {
        if ($this->hasActiveSuspensionLetter($events, [SuspensionType::SPECIFIED_ADJOURNMENT], $today)) {
            return true;
        }

        return $this->hasEventOfType($events, PetitionEventType::UNSPECIFIED_ADJOURNMENT)
            && !$this->hasEventOfType($events, PetitionEventType::UNSPECIFIED_ADJOURNMENT_END);
    }

    /**
     * @param Collection<int, PetitionEvent> $events
     */
    private function hasRunningAppealNotTimelyTerm(Collection $events, CalendarDate $today): bool
    {
        return $events->contains(static function (PetitionEvent $event) use ($today): bool {
            $duration = $event->duration ?? 0;

            if ($event->type !== PetitionEventType::APPEAL_DECISION_NOT_TIMELY || $duration === 0) {
                return false;
            }

            return $today->greaterThanOrEqualTo($event->date)
                && $today->lessThanOrEqualTo($event->date->addDays($duration));
        });
    }

    /**
     * @param Collection<int, PetitionEvent> $events
     */
    private function hasEventOfType(Collection $events, PetitionEventType $type): bool
    {
        return $events->contains(static fn(PetitionEvent $event): bool => $event->type === $type);
    }

    /**
     * @param Collection<int, PetitionEvent> $events
     *
     * @return Collection<int, PetitionEvent>
     */
    private function sortedByDate(Collection $events, PetitionEventType $type): Collection
    {
        return $events
            ->filter(static fn(PetitionEvent $event): bool => $event->type === $type)
            ->sortBy(static fn(PetitionEvent $event): string => $event->date->toDateString())
            ->values();
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
