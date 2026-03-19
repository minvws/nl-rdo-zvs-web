<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;

use function __;
use function in_array;

class DateMustNotBeInTermRule implements ValidationRuleInterface
{
    /**
     * @param array<string> $forbiddenTerms
     */
    public function __construct(
        private readonly array $forbiddenTerms,
    ) {
    }

    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        $state->buildCalendar();
        $day = $state->findDay($event->date);

        if (!$day instanceof EventCalendarDay) {
            return null;
        }
        if (in_array($day->applicableTerm, $this->forbiddenTerms, true)) {
            return new ValidationResult([
                'date' => __('term.validation.common.date_not_allowed_in_term', [
                    'event' => $event->type->label(),
                    'term' => __('term.' . $day->applicableTerm . '.default'),
                ]),
            ]);
        }

        return null;
    }
}
