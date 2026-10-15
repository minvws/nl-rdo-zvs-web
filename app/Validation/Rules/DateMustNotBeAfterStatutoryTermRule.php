<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Enums\DecisionPeriodType;
use App\Enums\TermType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;

use function __;

/**
 * An event that pauses the statutory decision term can only be added while that term is running.
 * Its last day is the last statutory day, or the labelled original last day when ending an
 * unspecified adjournment replaced the statutory days after it.
 */
readonly class DateMustNotBeAfterStatutoryTermRule implements ValidationRuleInterface
{
    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        $state->buildCalendar();

        $lastStatutoryDay = $state->getCalendar()
            ->filter(static fn(EventCalendarDay $day): bool => $day->isOriginalDeadline
                || ($day->applicableTerm === TermType::DECISION_PERIOD->value
                    && $day->decisionPeriodType === DecisionPeriodType::STATUTORY))
            ->sortByDesc(static fn(EventCalendarDay $day): string => $day->date->toDateString())
            ->first();

        if (!$lastStatutoryDay instanceof EventCalendarDay || !$event->date->greaterThan($lastStatutoryDay->date)) {
            return null;
        }

        return new ValidationResult([
            'date' => __('term.validation.common.date_after_statutory_term', [
                'event' => $event->type->label(),
            ]),
        ]);
    }
}
