<?php

declare(strict_types=1);

namespace App\Exports;

use App\Facades\DisplayDate;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ExcelDate
{
    public const string DATE_FORMAT = 'dd-mm-yyyy';
    public const string DATETIME_FORMAT = 'dd-mm-yyyy hh:mm';

    public static function fromCalendarDate(?CalendarDate $date): ?float
    {
        if (!$date instanceof CalendarDate) {
            return null;
        }

        return Date::formattedPHPToExcel((int) $date->format('Y'), (int) $date->format('m'), (int) $date->format('d'));
    }

    public static function fromDateTime(?CarbonImmutable $date): ?float
    {
        if (!$date instanceof CarbonImmutable) {
            return null;
        }

        return Date::dateTimeToExcel(DisplayDate::shift($date));
    }
}
