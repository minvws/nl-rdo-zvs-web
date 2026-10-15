<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Enums\TermType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;

use function __;
use function in_array;
use function lcfirst;

class DateMustNotBeInTermRule implements ValidationRuleInterface
{
    /**
     * @param array<TermType> $forbiddenTerms
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

        $term = TermType::tryFrom((string) $day->applicableTerm);
        if ($term instanceof TermType && in_array($term, $this->forbiddenTerms, true)) {
            return new ValidationResult([
                'date' => __('term.validation.common.date_not_allowed_in_term', [
                    'event' => $event->type->label(),
                    'term' => lcfirst($term->label()),
                ]),
            ]);
        }

        return null;
    }
}
