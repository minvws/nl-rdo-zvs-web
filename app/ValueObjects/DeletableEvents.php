<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Ramsey\Uuid\UuidInterface;

use function array_any;

/**
 * The event ids that can be deleted without leaving events that could not have been added in that order.
 */
readonly class DeletableEvents
{
    /**
     * @param array<int, UuidInterface> $eventIds
     */
    public function __construct(
        private array $eventIds,
    ) {
    }

    public function contains(PetitionEventData $event): bool
    {
        return array_any($this->eventIds, static fn(UuidInterface $id): bool => $id->equals($event->id));
    }
}
