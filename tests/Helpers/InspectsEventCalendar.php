<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Enums\TermType;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;

use function array_values;

trait InspectsEventCalendar
{
    /**
     * Every date from $from up to and including $to, as Y-m-d.
     *
     * @return list<string>
     */
    private static function dateRange(string $from, string $to): array
    {
        $dates = [];
        $end = CalendarDate::create($to);

        for ($date = CalendarDate::create($from); $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        return $dates;
    }

    /**
     * @param callable(EventCalendarDay): bool $condition
     *
     * @return list<string>
     */
    private function datesWhere(EventCalendar $calendar, callable $condition): array
    {
        return array_values($calendar
            ->filter($condition)
            ->map(static fn(EventCalendarDay $day): string => $day->date->toDateString())
            ->sort()
            ->all());
    }

    /**
     * @return list<string>
     */
    private function budgetDates(EventCalendar $calendar, TermType $term): array
    {
        return $this->datesWhere(
            $calendar,
            static fn(EventCalendarDay $day): bool => $day->isBudgetDay && $day->applicableTerm === $term->value,
        );
    }

    /**
     * @return list<string>
     */
    private function deadlineDates(EventCalendar $calendar, TermType $term): array
    {
        return $this->datesWhere(
            $calendar,
            static fn(EventCalendarDay $day): bool => $day->isDeadline && $day->applicableTerm === $term->value,
        );
    }

    /**
     * @return list<string>
     */
    private function atwDates(EventCalendar $calendar, TermType $term): array
    {
        return $this->datesWhere(
            $calendar,
            static fn(EventCalendarDay $day): bool => $day->isATW && $day->applicableTerm === $term->value,
        );
    }

    /**
     * @return list<string>
     */
    private function firstDayOfBudgetDates(EventCalendar $calendar, TermType $term): array
    {
        return $this->datesWhere(
            $calendar,
            static fn(EventCalendarDay $day): bool => $day->isFirstDayOfBudget && $day->applicableTerm === $term->value,
        );
    }

    /**
     * @return list<string>
     */
    private function lastDayOfBudgetDates(EventCalendar $calendar, TermType $term): array
    {
        return $this->datesWhere(
            $calendar,
            static fn(EventCalendarDay $day): bool => $day->isLastDayOfBudget && $day->applicableTerm === $term->value,
        );
    }
}
