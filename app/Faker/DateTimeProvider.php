<?php

declare(strict_types=1);

namespace App\Faker;

use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use DateTime;
use Faker\Provider\DateTime as FakerDateTime;
use Override;

class DateTimeProvider extends FakerDateTime
{
    public static function calendarDate(): CalendarDate
    {
        return CalendarDate::instance(static::dateTime());
    }

    public static function calendarDateWeekend(): CalendarDate
    {
        return CalendarDate::instance(static::dateTime()->nextWeekendDay());
    }

    public static function calendarDateWorkday(): CalendarDate
    {
        return CalendarDate::instance(static::dateTime()->nextWeekday());
    }

    /**
     * @param DateTime|string $max
     * @param string|null $timezone
     */
    #[Override]
    public static function dateTime($max = 'now', $timezone = null): CarbonImmutable
    {
        return CarbonImmutable::instance(parent::dateTime($max, $timezone));
    }

    /**
     * @param DateTime|string $startDate
     * @param DateTime|string $endDate
     * @param string|null $timezone
     */
    #[Override]
    public static function dateTimeBetween($startDate = '-30 years', $endDate = 'now', $timezone = null): CarbonImmutable
    {
        return CarbonImmutable::instance(parent::dateTimeBetween($startDate, $endDate, $timezone));
    }
}
