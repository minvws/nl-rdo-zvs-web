<?php

declare(strict_types=1);

namespace App\Services;

use App\ValueObjects\CalendarDate;

interface DeadlineCalculatorInterface
{
    public function calculate(CalendarDate $proposedDeadline): CalendarDate;
}
