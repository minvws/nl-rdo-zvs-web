<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\PetitionEventChange;
use App\Enums\PetitionEventType;

/**
 * A petition event as recorded in the timeline, with how it changed compared to the previous calendar.
 */
readonly class PetitionEventChangeData
{
    public function __construct(
        public PetitionEventType $type,
        public PetitionEventChange $change,
    ) {
    }

    public static function createForUnchanged(PetitionEventType $type): self
    {
        return new self($type, PetitionEventChange::UNCHANGED);
    }

    public static function createForAdded(PetitionEventType $type): self
    {
        return new self($type, PetitionEventChange::ADDED);
    }

    public static function createForRemoved(PetitionEventType $type): self
    {
        return new self($type, PetitionEventChange::REMOVED);
    }

    public function isAdded(): bool
    {
        return $this->change === PetitionEventChange::ADDED;
    }

    public function isRemoved(): bool
    {
        return $this->change === PetitionEventChange::REMOVED;
    }

    public function isChanged(): bool
    {
        return $this->change !== PetitionEventChange::UNCHANGED;
    }

    /**
     * @param array{type: string, change: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: PetitionEventType::from($data['type']),
            change: PetitionEventChange::from($data['change']),
        );
    }

    /**
     * @return array{type: string, change: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'change' => $this->change->value,
        ];
    }
}
