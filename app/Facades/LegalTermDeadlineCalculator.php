<?php

declare(strict_types=1);

namespace App\Facades;

use App\ValueObjects\CalendarDate;
use Illuminate\Support\Facades\Facade;

/**
 * @method static CalendarDate calculate(CalendarDate $proposedDeadline)
 * @method static bool isWeekendOrHoliday(CalendarDate $date)
 */
class LegalTermDeadlineCalculator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'legal-term-deadline-calculator';
    }
}
