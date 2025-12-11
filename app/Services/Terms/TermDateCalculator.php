<?php

declare(strict_types=1);

namespace App\Services\Terms;

use App\ValueObjects\CalendarDate;

final class TermDateCalculator
{
    public static function calculateEndDate(CalendarDate $startDate, int $durationInDays): CalendarDate
    {
        return $startDate->addDays($durationInDays - 1);
    }

    public static function calculateDuration(CalendarDate $startDate, CalendarDate $endDate): int
    {
        return $startDate->diffInDays($endDate) + 1;
    }
}
