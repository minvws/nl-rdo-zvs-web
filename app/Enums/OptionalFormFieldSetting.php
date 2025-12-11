<?php

declare(strict_types=1);

namespace App\Enums;

enum OptionalFormFieldSetting: string
{
    case REQUIRED = 'required';
    case EXCLUDED = 'excluded';
    case OPTIONAL = 'optional';

    public function getValidationRule(): string
    {
        return match ($this) {
            self::REQUIRED => 'required',
            self::EXCLUDED => 'exclude',
            self::OPTIONAL => 'nullable',
        };
    }
}
