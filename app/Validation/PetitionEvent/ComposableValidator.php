<?php

declare(strict_types=1);

namespace App\Validation\PetitionEvent;

use App\Services\DerivedState;
use App\Services\EventValidatorInterface;
use App\Services\ValidationResult;
use App\Validation\Rules\ValidationRuleInterface;
use App\ValueObjects\PetitionEventData;

class ComposableValidator implements EventValidatorInterface
{
    /**
     * @param array<ValidationRuleInterface> $rules
     */
    public function __construct(
        private readonly array $rules,
    ) {
    }

    public function validate(PetitionEventData $event, DerivedState $state): ValidationResult
    {
        foreach ($this->rules as $rule) {
            $result = $rule->validate($event, $state);
            if ($result !== null) {
                return $result;
            }
        }

        return new ValidationResult([]);
    }
}
