<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Illuminate\Support\Collection;

use function collect;
use function range;

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

            $matchingEnd = $ends->first(static function (PetitionEventData $end) use ($firstDay): bool {
                return $end->date->greaterThanOrEqualTo($firstDay);
            });

            $lastDay = $matchingEnd
                ? $matchingEnd->date->subDay()
                : $firstDay->addDays($start->duration - 1);

            $days = $firstDay->diffInDays($lastDay);

            collect(range(0, $days))->each(static function (int $i) use ($firstDay, $calendar, $start): void {
                $calendar->addDay(EventCalendarDay::new(
                    $firstDay->addDays($i),
                    [
                        'suspensionType' => $start->suspensionType,
                    ],
                ));
            });
        });
    }
}
