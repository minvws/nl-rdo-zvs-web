<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PublicHoliday;
use App\ValueObjects\CalendarDate;

final readonly class LegalTermDeadlineCalculator implements DeadlineCalculatorInterface
{
    public function calculate(CalendarDate $proposedDeadline): CalendarDate
    {
        while ($this->isWeekendOrHoliday($proposedDeadline)) {
            $proposedDeadline = $proposedDeadline->addDay();
        }

        return $proposedDeadline;
    }

    public function isWeekendOrHoliday(CalendarDate $date): bool
    {
        if ($date->isWeekend()) {
            return true;
        }

        return $this->isPublicHoliday($date);
    }

    private function isPublicHoliday(CalendarDate $date): bool
    {
        return PublicHoliday::query()
            ->where('date', $date)
            ->exists();
    }
}
