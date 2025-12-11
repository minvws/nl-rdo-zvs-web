<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\ValueObjects\CalendarDate;
use BackedEnum;
use Illuminate\Foundation\Http\FormRequest as IlluminateFormRequest;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

class FormRequest extends IlluminateFormRequest
{
    public function getBoolean(string $key): bool
    {
        return $this->has($key) && $this->boolean($key) === true;
    }

    public function getCalendarDate(string $key): CalendarDate
    {
        return CalendarDate::createFromFormat(CalendarDate::DEFAULT_STRING_FORMAT, $this->getString($key));
    }

    public function getCalendarDateOrNull(string $key): ?CalendarDate
    {
        $value = $this->input($key);

        if ($value === null) {
            return null;
        }

        return $this->getCalendarDate($key);
    }

    /**
     * @template TEnum of BackedEnum
     *
     * @param class-string<TEnum> $enumClass
     *
     * @return TEnum
     */
    public function getEnum(string $key, string $enumClass)
    {
        $value = $this->enum($key, $enumClass);
        Assert::isInstanceOf($value, $enumClass);

        return $value;
    }

    public function getString(string $key): string
    {
        $value = $this->input($key);
        Assert::string($value);

        return $value;
    }

    public function getUuid(string $key): UuidInterface
    {
        $value = $this->input($key);
        Assert::string($value);

        return Uuid::fromString($value);
    }

    public function getUuidOrNull(string $key): ?UuidInterface
    {
        $value = $this->input($key);
        Assert::nullOrString($value);

        if ($value === null) {
            return null;
        }

        return Uuid::fromString($value);
    }
}
