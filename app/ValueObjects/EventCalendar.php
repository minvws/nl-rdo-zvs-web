<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\SuspensionType;
use Illuminate\Support\Collection;

use function array_filter;
use function uasort;

/**
 * @extends Collection<string, EventCalendarDay>
 */
class EventCalendar extends Collection
{
    public int $totalDaysSuspended = 0;

    public function addDay(EventCalendarDay $day): void
    {
        $this->put($day->date->toDateString(), $day);
    }

    public function findDay(CalendarDate $date): ?EventCalendarDay
    {
        return $this->get($date->toDateString());
    }

    /**
     * @param array<string, mixed> $properties
     */
    public function upsertDay(CalendarDate $date, array $properties): void
    {
        $existingDay = $this->findDay($date);

        $this->items[$date->toDateString()] = $existingDay instanceof EventCalendarDay
            ? EventCalendarDay::fromProperties($existingDay, $properties)
            : EventCalendarDay::new($date, $properties);
    }

    public function isOpschorting(CalendarDate $date): bool
    {
        $day = $this->findDay($date);

        return $day instanceof EventCalendarDay && $day->suspensionType instanceof SuspensionType;
    }

    public function removeAllDaysAfterDate(CalendarDate $date): void
    {
        $this->items = array_filter(
            $this->items,
            static function (mixed $day) use ($date): bool {
                /** @var EventCalendarDay $day */
                return $day->date->lessThanOrEqualTo($date);
            },
        );
    }

    public function sortByDate(): void
    {
        uasort($this->items, static function (EventCalendarDay $a, EventCalendarDay $b): int {
            return $a->date <=> $b->date;
        });
    }
}
