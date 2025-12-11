<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\ValueObjects\PetitionEventData;

interface ValidationRuleInterface
{
    /**
     * Validate the event against this rule.
     *
     * Returns ValidationResult with errors if validation fails, null if validation passes.
     */
    public function validate(PetitionEventData $event, DerivedState $state): ?ValidationResult;
}
