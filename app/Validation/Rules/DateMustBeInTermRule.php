<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;

use function __;
use function in_array;

class DateMustBeInTermRule implements ValidationRuleInterface
{
    /**
     * @param array<string> $allowedTerms
     */
    public function __construct(
        private readonly array $allowedTerms,
    ) {
    }

    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        $state->buildCalendar();
        $day = $state->findDay($event->date);

        if (!$day instanceof EventCalendarDay || !in_array($day->applicableTerm, $this->allowedTerms, true)) {
            return new ValidationResult([
                'date' => __('term.validation.common.date_must_be_in_term', [
                    'event' => $event->type->label(),
                ]),
            ]);
        }

        return null;
    }
}
