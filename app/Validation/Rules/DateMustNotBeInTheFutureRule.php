<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;

use function __;

class DateMustNotBeInTheFutureRule implements ValidationRuleInterface
{
    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        if (!CalendarDate::today()->isBefore($event->date)) {
            return null;
        }

        return new ValidationResult([
            'date' => __('term.validation.common.date_must_not_be_in_the_future', [
                'event' => $event->type->label(),
            ]),
        ]);
    }
}
