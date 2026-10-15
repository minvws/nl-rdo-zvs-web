<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

class SuspensionPeriodGenerator implements PeriodGeneratorInterface
{
    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function generate(Collection $events, EventCalendar $calendar): void
    {
        $starts = $events->filter(
            static fn(PetitionEventData $petitionEventData): bool => $petitionEventData->type === PetitionEventType::LETTER_OF_SUSPENSION_SENT,
        )->sortBy(
            static fn(PetitionEventData $petitionEventData): string => $petitionEventData->date->toDateString(),
        )->values();
        $ends = $events->filter(
            static fn(PetitionEventData $petitionEventData): bool => $petitionEventData->type === PetitionEventType::SUSPENSION_END,
        )->sortBy(
            static fn(PetitionEventData $petitionEventData): string => $petitionEventData->date->toDateString(),
        )->values();

        $starts->each(static function (PetitionEventData $start) use ($ends, $calendar): void {
            $firstDay = $start->date->addDay();

            $matchingEnd = $ends->first(static fn(PetitionEventData $end): bool => $end->date->greaterThanOrEqualTo($firstDay));

            // The end day itself is no longer suspended, so an end on the first day suspends nothing
            $lastDay = $matchingEnd
                ? $matchingEnd->date->subDay()
                : $firstDay->addDays(($start->duration ?? 0) - 1);

            for ($date = $firstDay; $date->lessThanOrEqualTo($lastDay); $date = $date->addDay()) {
                $calendar->addDay(EventCalendarDay::new($date, [
                    'suspensionType' => $start->suspensionType,
                ]));
            }
        });
    }
}
