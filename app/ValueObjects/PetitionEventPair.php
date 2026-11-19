<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\PetitionEventType;
use Illuminate\Support\Collection;

/**
 * A start event and the event that ends it, for the periods that are registered as two separate events.
 * The events are not linked to each other, so the pairing below is the only place that decides which end
 * belongs to which start. The period generators and the notification rules both rely on it.
 */
readonly class PetitionEventPair
{
    public function __construct(
        public PetitionEventData $start,
        public ?PetitionEventData $end,
    ) {
    }

    /**
     * Each suspension letter is ended by the first suspension end dated after the letter.
     *
     * @param Collection<int, PetitionEventData> $events
     *
     * @return Collection<int, self>
     */
    public static function suspensions(Collection $events): Collection
    {
        $ends = self::sortedEventsOfType($events, PetitionEventType::SUSPENSION_END);

        return self::sortedEventsOfType($events, PetitionEventType::LETTER_OF_SUSPENSION_SENT)
            ->map(static function (PetitionEventData $start) use ($ends): self {
                $firstDay = $start->date->addDay();

                return new self(
                    $start,
                    $ends->first(static fn(PetitionEventData $end): bool => $end->date->greaterThanOrEqualTo($firstDay)),
                );
            });
    }

    /**
     * Unspecified adjournments are paired with their ends in chronological order. An adjournment without
     * an end is still open.
     *
     * @param Collection<int, PetitionEventData> $events
     *
     * @return Collection<int, self>
     */
    public static function unspecifiedAdjournments(Collection $events): Collection
    {
        $ends = self::sortedEventsOfType($events, PetitionEventType::UNSPECIFIED_ADJOURNMENT_END);

        return self::sortedEventsOfType($events, PetitionEventType::UNSPECIFIED_ADJOURNMENT)
            ->map(static fn(PetitionEventData $start, int $index): self => new self($start, $ends->get($index)));
    }

    /**
     * @param Collection<int, PetitionEventData> $events
     *
     * @return Collection<int, PetitionEventData>
     */
    private static function sortedEventsOfType(Collection $events, PetitionEventType $type): Collection
    {
        return $events
            ->filter(static fn(PetitionEventData $event): bool => $event->type === $type)
            ->sortBy(static fn(PetitionEventData $event): string => $event->date->toDateString())
            ->values();
    }
}
