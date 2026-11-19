<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\PetitionEventData;

use function __;
use function in_array;
use function is_array;

/**
 * Like DateMustBeAfterDependencyRule, but the event may also take place on the day of the dependency.
 * Without the dependency there is nothing to compare with, RequiresDependencyRule is for that.
 */
class DateMustBeOnOrAfterDependencyRule implements ValidationRuleInterface
{
    /** @var array<int, PetitionEventType> */
    private readonly array $dependencyTypes;

    /**
     * @param PetitionEventType|array<int, PetitionEventType> $dependencyTypes the event is compared with the first of these that exists
     */
    public function __construct(
        PetitionEventType|array $dependencyTypes,
    ) {
        $this->dependencyTypes = is_array($dependencyTypes) ? $dependencyTypes : [$dependencyTypes];
    }

    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        $dependencyEvent = $state->getEvents()->first(
            fn(PetitionEventData $petitionEventData): bool => in_array($petitionEventData->type, $this->dependencyTypes, true),
        );

        if ($dependencyEvent !== null && $event->date->isBefore($dependencyEvent->date)) {
            return new ValidationResult([
                'date' => __('term.validation.common.date_must_be_on_or_after_dependency', [
                    'event' => $event->type->label(),
                    'dependency' => $dependencyEvent->type->label(),
                ]),
            ]);
        }

        return null;
    }
}
