<?php

declare(strict_types=1);

namespace App\Services;

use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Webmozart\Assert\Assert;

readonly class DisplayDateService
{
    // format: https://momentjs.com/docs/#/parsing/string-format/
    private const string DATE_FORMAT = 'DD-MM-YYYY';
    private const string DATETIME_FORMAT = 'DD-MM-YYYY HH:mm';
    private const string SENTENCE_FORMAT_DATE = 'D MMMM YYYY';
    private const string SENTENCE_FORMAT_DATETIME = 'D MMMM YYYY [om] HH:mm';
    private const string TIME_FORMAT = 'HH:mm';

    public function __construct(
        private string $displayTimezone,
        private string $locale,
    ) {
    }

    public function date(CalendarDate|CarbonImmutable $date): string
    {
        return $this->shiftAndFormat($date, self::DATE_FORMAT);
    }

    public function datetime(CarbonImmutable $date): string
    {
        return $this->shiftAndFormat($date, self::DATETIME_FORMAT);
    }

    public function diffInDays(CalendarDate $start, CalendarDate $end): int
    {
        return $start->diffInDays($end);
    }

    public function sentence(CalendarDate|CarbonImmutable $date): string
    {
        return $this->shiftAndFormat($date, $date instanceof CalendarDate ? self::SENTENCE_FORMAT_DATE : self::SENTENCE_FORMAT_DATETIME);
    }

    public function time(CarbonImmutable $date): string
    {
        return $this->shiftAndFormat($date, self::TIME_FORMAT);
    }

    public function shift(CarbonImmutable $date): CarbonImmutable
    {
        return $date->setTimezone($this->displayTimezone);
    }

    private function format(CalendarDate|CarbonImmutable $date, string $format): string
    {
        $localisedDate = $date->locale($this->locale);

        Assert::object($localisedDate);
        Assert::isInstanceOfAny($localisedDate, [CalendarDate::class, CarbonImmutable::class]);

        return $localisedDate->isoFormat($format);
    }

    private function shiftAndFormat(CalendarDate|CarbonImmutable $date, string $format): string
    {
        if ($date instanceof CarbonImmutable) {
            $date = $this->shift($date);
        }

        return $this->format($date, $format);
    }
}
