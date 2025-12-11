<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use DateTimeInterface;
use JsonSerializable;
use Stringable;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings(PHPMD)
 */
class CalendarDate implements JsonSerializable, Stringable // NOSONAR
{
    public const string DEFAULT_STRING_FORMAT = 'Y-m-d';
    private const string DEFAULT_TIMEZONE = 'utc';

    private CarbonInterface $date;

    private function __construct(
        CarbonInterface $date,
    ) {
        $date = $date->setTimezone(self::DEFAULT_TIMEZONE);
        $date = $date->floorDay();

        $this->date = $date;
    }

    public function __toString(): string
    {
        return $this->format(self::DEFAULT_STRING_FORMAT);
    }

    public static function instance(DateTimeInterface $date): self
    {
        return new self(CarbonImmutable::instance($date));
    }

    public static function create(string $time): self
    {
        try {
            return new self(new CarbonImmutable($time));
        } catch (InvalidFormatException $invalidFormatException) {
            throw new CalendarDateException('invalid format', 0, $invalidFormatException);
        }
    }

    public static function createFromFormat(string $format, string $time): self
    {
        $date = CarbonImmutable::createFromFormat($format, $time);

        Assert::isInstanceOf($date, DateTimeInterface::class);

        return new self($date);
    }

    public function toDateString(): string
    {
        return $this->date->toDateString();
    }

    public function addDay(): self
    {
        return new self($this->date->addDay());
    }

    public function addDays(int $value): self
    {
        return new self($this->date->addDays($value));
    }

    public function isBefore(CalendarDate $date): bool
    {
        return $this->date->isBefore($date->date);
    }

    public function subDay(): self
    {
        return new self($this->date->subDay());
    }

    public function subDays(int $value): self
    {
        return new self($this->date->subDays($value));
    }

    public function diffInDays(self $end): int
    {
        $endDate = CarbonImmutable::createFromFormat(self::DEFAULT_STRING_FORMAT, $end->format(self::DEFAULT_STRING_FORMAT));
        Assert::isInstanceOf($endDate, CarbonImmutable::class);

        return (int) $this->date->diffInDays(
            $endDate->floorDay(),
        );
    }

    public function format(string $format): string
    {
        return $this->date->format($format);
    }

    public function translatedFormat(string $format): string
    {
        return $this->date->translatedFormat($format);
    }

    public function isoFormat(string $format, ?string $originalFormat = null): string
    {
        return $this->date->isoFormat($format, $originalFormat);
    }

    public function isWeekend(): bool
    {
        return $this->date->isWeekend();
    }

    public function isInThePast(): bool
    {
        if ($this->date->isToday()) {
            return false;
        }

        return $this->date->isPast();
    }

    public function locale(?string $locale = null, string ...$fallbackLocales): self
    {
        $dateWithLocale = $this->date->locale($locale, ...$fallbackLocales);
        Assert::isInstanceOf($dateWithLocale, CarbonInterface::class);
        $this->date = $dateWithLocale;

        return $this;
    }

    public static function today(): self
    {
        return new self(CarbonImmutable::now());
    }

    public function equals(CalendarDate $date): bool
    {
        return $this->date->equalTo($date->format('d-m-Y'));
    }

    public function greaterThan(CalendarDate $date): bool
    {
        return $this->date->greaterThan($date->date);
    }

    public function greaterThanOrEqualTo(CalendarDate $date): bool
    {
        return $this->date->greaterThanOrEqualTo($date->date);
    }

    public function lessThanOrEqualTo(CalendarDate $date): bool
    {
        return $this->date->lessThanOrEqualTo($date->date);
    }

    public function jsonSerialize(): string // NOSONAR
    {
        return $this->format(self::DEFAULT_STRING_FORMAT);
    }
}
