<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class WizardEventCollection
{
    /**
     * @param Collection<int, PetitionEventData> $events
     */
    public function __construct(
        private readonly Collection $events,
    ) {
    }

    public static function make(): self
    {
        return new self(new Collection());
    }

    public function add(PetitionEventData $event): self
    {
        $newEvents = new Collection($this->events->all());
        $newEvents->push($event);

        return new self($newEvents);
    }

    public function removeLast(): self
    {
        if ($this->events->isEmpty()) {
            return $this;
        }

        $newEvents = new Collection($this->events->all());
        $newEvents->pop();

        return new self($newEvents);
    }

    /**
     * @return Collection<int, PetitionEventData>
     */
    public function all(): Collection
    {
        return $this->events;
    }

    public function isEmpty(): bool
    {
        return $this->events->isEmpty();
    }

    public function count(): int
    {
        return $this->events->count();
    }

    public function last(): ?PetitionEventData
    {
        return $this->events->last();
    }

    /**
     * @return array<int, array{type: string, date: string, duration?: int|null, penalties?: array<int, array{amount: int, duration: int}>, created_at: CarbonImmutable}>
     */
    public function toArray(): array
    {
        return $this->events->map(
            static fn(PetitionEventData $event): array => $event->toArray(),
        )->all();
    }
}
