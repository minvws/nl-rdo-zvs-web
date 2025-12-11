<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\PetitionEventData;

use function __;

class DateMustBeInSuspensionRule implements ValidationRuleInterface
{
    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        $state->buildCalendar();

        if (!$state->isOpschorting($event->date)) {
            return new ValidationResult([
                'date' => __('term.validation.common.date_must_be_in_suspension'),
            ]);
        }

        return null;
    }
}
