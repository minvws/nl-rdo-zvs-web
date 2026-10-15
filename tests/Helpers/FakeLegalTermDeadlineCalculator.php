<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Services\DeadlineCalculatorInterface;
use App\ValueObjects\CalendarDate;
use Illuminate\Contracts\Foundation\Application;

use function in_array;

/**
 * Moves a deadline past weekends and the given public holidays without touching the database, so
 * term calculations can be tested against holidays deterministically.
 */
final readonly class FakeLegalTermDeadlineCalculator implements DeadlineCalculatorInterface
{
    /**
     * @param list<string> $publicHolidays dates as Y-m-d
     */
    public function __construct(private array $publicHolidays = [])
    {
    }

    /**
     * @param list<string> $publicHolidays dates as Y-m-d
     */
    public static function bind(Application $app, array $publicHolidays = []): void
    {
        $app->instance('legal-term-deadline-calculator', new self($publicHolidays));
    }

    public function calculate(CalendarDate $proposedDeadline): CalendarDate
    {
        while ($this->isWeekendOrHoliday($proposedDeadline)) {
            $proposedDeadline = $proposedDeadline->addDay();
        }

        return $proposedDeadline;
    }

    public function isWeekendOrHoliday(CalendarDate $date): bool
    {
        return $date->isWeekend() || in_array($date->toDateString(), $this->publicHolidays, true);
    }
}
