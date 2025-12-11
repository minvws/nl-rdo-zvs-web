<?php

declare(strict_types=1);

namespace App\Faker;

use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Faker\Generator as FakerGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * @method CalendarDate calendarDate()
 * @method CalendarDate calendarDateWeekend()
 * @method CalendarDate calendarDateWorkday()
 * @method CarbonImmutable dateTime($max = 'now', $timezone = null)
 * @method CarbonImmutable dateTimeBetween($startDate = '-30 years', $endDate = 'now', $timezone = null)
 * @method self optional(float $weight = 0.5, $default = null)
 * @method UuidInterface uuid()
 */
class Generator extends FakerGenerator
{
}
