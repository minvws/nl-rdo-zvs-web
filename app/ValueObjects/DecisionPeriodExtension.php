<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\DecisionPeriodType;
use Webmozart\Assert\Assert;

use function array_flip;
use function array_last;
use function array_map;

/**
 * The days an extending event ("verdaging", "commissiezitting", "afspraak") adds to the decision
 * period, in order, each with whether it spends budget. The last one always does.
 */
readonly class DecisionPeriodExtension
{
    public CalendarDate $lastDay;

    /** @var array<string, int> */
    private array $dateIndex;

    /**
     * @param list<array{date: CalendarDate, isBudgetDay: bool}> $days
     */
    public function __construct(
        public DecisionPeriodType $type,
        public array $days,
    ) {
        Assert::notEmpty($days);

        $this->lastDay = array_last($days)['date'];
        $this->dateIndex = array_flip(array_map(static fn(array $day): string => $day['date']->toDateString(), $days));
    }

    public function contains(CalendarDate $date): bool
    {
        return isset($this->dateIndex[$date->toDateString()]);
    }
}
