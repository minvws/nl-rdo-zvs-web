<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\ValueObjects\EventCalendar;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

use function usort;

class EventsGenerator implements PeriodGeneratorInterface
{
    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function generate(Collection $events, EventCalendar $calendar): void
    {
        foreach ($events as $event) {
            $eventDate = $event->date;
            $existingDay = $calendar->findDay($eventDate);

            $existingEvents = $existingDay->petitionEvents ?? [];

            $existingEvents[] = $event;

            usort(
                $existingEvents,
                static function (PetitionEventData $eventA, PetitionEventData $eventB): int {
                    return $eventA->createdAt <=> $eventB->createdAt;
                },
            );

            $calendar->upsertDay($eventDate, [
                'petitionEvents' => $existingEvents,
            ]);
        }
    }
}
