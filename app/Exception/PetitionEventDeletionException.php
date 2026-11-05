<?php

declare(strict_types=1);

namespace App\Exception;

use Ramsey\Uuid\UuidInterface;

use function sprintf;

class PetitionEventDeletionException extends DomainException
{
    public static function eventNotFound(UuidInterface $id): self
    {
        return new self(sprintf('The event "%s" is not one of the events in the wizard', $id->toString()));
    }

    public static function cannotBeDeleted(UuidInterface $id): self
    {
        return new self(sprintf('Deleting the event "%s" leaves events that could not have been added', $id->toString()));
    }
}
