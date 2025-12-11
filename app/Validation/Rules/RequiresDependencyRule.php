<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\PetitionEventData;

use function __;
use function array_map;
use function count;
use function implode;
use function in_array;
use function is_array;

class RequiresDependencyRule implements ValidationRuleInterface
{
    /** @var array<int, PetitionEventType> */
    private readonly array $requiredEventTypes;

    /**
     * @param PetitionEventType|array<int, PetitionEventType> $requiredEventTypes
     */
    public function __construct(
        PetitionEventType|array $requiredEventTypes,
    ) {
        $this->requiredEventTypes = is_array($requiredEventTypes) ? $requiredEventTypes : [$requiredEventTypes];
    }

    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        $hasAnyRequiredEvent = $state->getEvents()->contains(
            fn(PetitionEventData $petitionEventData): bool =>
                in_array($petitionEventData->type, $this->requiredEventTypes, true),
        );

        if (!$hasAnyRequiredEvent) {
            if (count($this->requiredEventTypes) === 1) {
                return new ValidationResult([
                    'general' => __('term.validation.common.event_requires_dependency', [
                        'event' => $event->type->label(),
                        'required_event' => $this->requiredEventTypes[0]->label(),
                    ]),
                ]);
            }

            $requiredLabels = array_map(
                static fn(PetitionEventType $type): string => $type->label(),
                $this->requiredEventTypes,
            );

            return new ValidationResult([
                'general' => __('term.validation.common.event_requires_dependency_any', [
                    'event' => $event->type->label(),
                    'required_events' => implode(' of ', $requiredLabels),
                ]),
            ]);
        }

        return null;
    }
}
