<?php

declare(strict_types=1);

namespace App\Services\NotificationRule;

use App\Enums\NotificationRuleAnchor;
use App\Enums\PetitionEventType;
use App\Factories\PetitionEventDataFactory;
use App\Models\NotificationRule;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\PetitionEventPair;
use Illuminate\Support\Collection;

use function collect;
use function in_array;

/**
 * Finds the dates a notification rule counts its days from, for one petition.
 */
class NotificationRuleAnchorResolver
{
    /**
     * The event types that have an end date a rule can count from.
     */
    public const array EVENT_TYPES_WITH_END_DATE = [
        PetitionEventType::MEETING_SCHEDULED,
        PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
        PetitionEventType::LETTER_OF_SUSPENSION_SENT,
        PetitionEventType::UNSPECIFIED_ADJOURNMENT,
    ];

    public static function hasEndDate(?PetitionEventType $type): bool
    {
        return in_array($type, self::EVENT_TYPES_WITH_END_DATE, true);
    }

    /**
     * @return Collection<int, CalendarDate>
     */
    public function anchorDates(NotificationRule $rule, Petition $petition): Collection
    {
        $events = $petition->petitionEvents->map(
            static fn(PetitionEvent $event): PetitionEventData => PetitionEventDataFactory::fromModel($event),
        );

        return match ($rule->anchor) {
            NotificationRuleAnchor::EVENT_DATE => $events
                ->filter(static fn(PetitionEventData $event): bool => $event->type === $rule->event_type)
                ->map(static fn(PetitionEventData $event): CalendarDate => $event->date)
                ->values(),
            NotificationRuleAnchor::EVENT_END_DATE => $this->endDates($rule->event_type, $events),
            NotificationRuleAnchor::PETITION_DEADLINE => $this->deadline($rule->event_type, $events, $petition),
        };
    }

    /**
     * The event type of a deadline rule is a condition: the rule only counts from the deadline of petitions that
     * have such an event. A decided petition has no deadline left, even when an override was set before. While an
     * unspecified adjournment is open the petition is on hold, so its deadline is no real deadline for the handler,
     * whether the department freezes or continues its terms. Where it freezes them the deadline also moves forward
     * every day, so counting from it would repeat the notification daily.
     *
     * @param Collection<int, PetitionEventData> $events
     *
     * @return Collection<int, CalendarDate>
     */
    private function deadline(PetitionEventType $type, Collection $events, Petition $petition): Collection
    {
        $hasEvent = $events->contains(static fn(PetitionEventData $event): bool => $event->type === $type);
        $isDecided = $events->contains(static fn(PetitionEventData $event): bool => $event->type === PetitionEventType::FINAL_RESULT);
        $isAdjourned = PetitionEventPair::unspecifiedAdjournments($events)
            ->contains(static fn(PetitionEventPair $adjournment): bool => !$adjournment->end instanceof PetitionEventData);

        $deadline = $hasEvent && !$isDecided && !$isAdjourned ? $petition->effective_deadline_at : null;

        return collect([$deadline])->whereInstanceOf(CalendarDate::class)->values();
    }

    /**
     * @param Collection<int, PetitionEventData> $events
     *
     * @return Collection<int, CalendarDate>
     */
    private function endDates(PetitionEventType $type, Collection $events): Collection
    {
        $endDates = match ($type) {
            PetitionEventType::LETTER_OF_SUSPENSION_SENT => PetitionEventPair::suspensions($events)->map(
                static fn(PetitionEventPair $suspension): ?CalendarDate => $suspension->end->date
                    ?? self::endOfDuration($suspension->start),
            ),
            PetitionEventType::UNSPECIFIED_ADJOURNMENT => PetitionEventPair::unspecifiedAdjournments($events)->map(
                static fn(PetitionEventPair $adjournment): ?CalendarDate => $adjournment->end?->date,
            ),
            PetitionEventType::MEETING_SCHEDULED, PetitionEventType::APPEAL_DECISION_NOT_TIMELY => $events
                ->filter(static fn(PetitionEventData $event): bool => $event->type === $type)
                ->map(static fn(PetitionEventData $event): ?CalendarDate => self::endOfDuration($event)),
            default => collect(),
        };

        return $endDates->whereInstanceOf(CalendarDate::class)->values();
    }

    /**
     * The last day of an event registered with a number of days, as `AddPetitionEventRequest` stores it.
     */
    private static function endOfDuration(PetitionEventData $event): ?CalendarDate
    {
        return $event->duration === null ? null : $event->date->addDays($event->duration);
    }
}
