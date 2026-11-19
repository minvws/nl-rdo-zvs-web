<?php

declare(strict_types=1);

namespace App\Services\PeriodGenerators;

use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\PetitionEventPair;
use Illuminate\Support\Collection;

class SuspensionPeriodGenerator implements PeriodGeneratorInterface
{
    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function generate(Collection $events, EventCalendar $calendar): void
    {
        PetitionEventPair::suspensions($events)->each(static function (PetitionEventPair $suspension) use ($calendar): void {
            $start = $suspension->start;
            $firstDay = $start->date->addDay();

            // The end day itself is no longer suspended, so an end on the first day suspends nothing
            $lastDay = $suspension->end instanceof PetitionEventData
                ? $suspension->end->date->subDay()
                : $firstDay->addDays(($start->duration ?? 0) - 1);

            for ($date = $firstDay; $date->lessThanOrEqualTo($lastDay); $date = $date->addDay()) {
                $calendar->addDay(EventCalendarDay::new($date, [
                    'suspensionType' => $start->suspensionType,
                ]));
            }
        });
    }
}
