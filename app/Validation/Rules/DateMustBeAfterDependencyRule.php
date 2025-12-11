<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\PetitionEventData;

use function __;

class DateMustBeAfterDependencyRule implements ValidationRuleInterface
{
    public function __construct(
        private readonly PetitionEventType $dependencyType,
    ) {
    }

    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        $dependencyEvent = $state->getEvents()->first(
            fn(PetitionEventData $petitionEventData): bool => $petitionEventData->type === $this->dependencyType,
        );

        if ($dependencyEvent !== null && $event->date->lessThanOrEqualTo($dependencyEvent->date)) {
            return new ValidationResult([
                'date' => __('term.validation.common.date_must_be_after_dependency', [
                    'event' => $event->type->label(),
                    'dependency' => $this->dependencyType->label(),
                ]),
            ]);
        }

        return null;
    }
}
