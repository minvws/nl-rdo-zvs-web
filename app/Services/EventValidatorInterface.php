<?php

declare(strict_types=1);

namespace App\Services;

use App\ValueObjects\PetitionEventData;

interface EventValidatorInterface
{
    public function validate(PetitionEventData $event, DerivedState $state): ValidationResult;
}
