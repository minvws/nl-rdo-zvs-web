<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\PetitionEventData;

use function __;

class UniquenessRule implements ValidationRuleInterface
{
    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        $exists = $state->getEvents()->contains(
            static fn(PetitionEventData $petitionEventData): bool => $petitionEventData->type === $event->type,
        );

        if ($exists) {
            return new ValidationResult([
                'general' => __('term.validation.common.only_one_event_allowed', [
                    'event' => $event->type->label(),
                ]),
            ]);
        }

        return null;
    }
}
