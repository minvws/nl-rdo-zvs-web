<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\PetitionEventData;

use function __;
use function in_array;

class MustBeInTermTypeRule implements ValidationRuleInterface
{
    /**
     * @param array<PetitionEventType> $allowedTypes
     */
    public function __construct(
        private readonly array $allowedTypes,
    ) {
    }

    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult
    {
        if (!in_array($event->type, $this->allowedTypes, true)) {
            return new ValidationResult([
                'type' => __('term.validation.common.event_not_allowed_in_term_type', [
                    'event' => $event->type->label(),
                ]),
            ]);
        }

        return null;
    }
}
