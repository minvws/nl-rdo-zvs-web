<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\DisplayDateService;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Facade;

/**
 * @see DisplayDateService
 *
 * @method static string date(CalendarDate|CarbonImmutable $date)
 * @method static int diffInDays(CalendarDate|CarbonImmutable $start, CalendarDate|CarbonImmutable $end)
 * @method static string datetime(CarbonImmutable $date)
 * @method static string sentence(CalendarDate|CarbonImmutable $date)
 * @method static string time(CarbonImmutable $date)
 */
class DisplayDate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DisplayDateService::class;
    }
}
