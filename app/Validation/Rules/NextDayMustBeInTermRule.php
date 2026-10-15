<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Enums\TermType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\Support\Str\TermsString;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;

use function __;
use function in_array;

readonly class NextDayMustBeInTermRule implements ValidationRuleInterface
{
    /**
     * @param array<TermType> $allowedTerms
     */
    public function __construct(
        private array $allowedTerms,
    ) {
    }

    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        $state->buildCalendar();
        $day = $state->findDay($event->date->addDay());
        $term = $day instanceof EventCalendarDay ? TermType::tryFrom((string) $day->applicableTerm) : null;

        if (!in_array($term, $this->allowedTerms, true)) {
            return new ValidationResult([
                'date' => __('term.validation.common.date_must_be_in_term', [
                    'event' => $event->type->label(),
                    'term' => TermsString::format($this->allowedTerms),
                ]),
            ]);
        }

        return null;
    }
}
