<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Webmozart\Assert\Assert;

readonly class DateRange
{
    public function __construct(
        private CalendarDate $startDate,
        private CalendarDate $endDate,
    ) {
        Assert::greaterThanEq($endDate, $startDate);
    }

    public function getStartDate(): CalendarDate
    {
        return $this->startDate;
    }

    public function getEndDate(): CalendarDate
    {
        return $this->endDate;
    }
}
