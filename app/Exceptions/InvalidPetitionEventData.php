<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\PetitionEventType;
use DomainException;

use function sprintf;

class InvalidPetitionEventData extends DomainException
{
    public static function suspensionTypeNotAllowed(PetitionEventType $type): self
    {
        return new self(
            sprintf('Event type "%s" does not support suspension type', $type->value),
        );
    }

    public static function resultTypeNotAllowed(PetitionEventType $type): self
    {
        return new self(
            sprintf('Event type "%s" does not support result type', $type->value),
        );
    }

    public static function hearingFormNotAllowed(PetitionEventType $type): self
    {
        return new self(
            sprintf('Event type "%s" does not support hearing form', $type->value),
        );
    }

    public static function adjournmentEndReasonNotAllowed(PetitionEventType $type): self
    {
        return new self(
            sprintf('Event type "%s" does not support adjournment end reason', $type->value),
        );
    }
}
