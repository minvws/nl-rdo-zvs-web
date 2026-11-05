<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\PetitionEventType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

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

    public function find(UuidInterface $id): ?PetitionEventData
    {
        return $this->events->first(static fn(PetitionEventData $event): bool => $event->id->equals($id));
    }

    public function remove(UuidInterface $id): self
    {
        return new self($this->events->reject(static fn(PetitionEventData $event): bool => $event->id->equals($id))->values());
    }

    /**
     * The events that were added before the event.
     */
    public function before(PetitionEventData $event): self
    {
        return new self($this->events->take($this->positionOf($event))->values());
    }

    /**
     * The events that were added after the event.
     */
    public function after(PetitionEventData $event): self
    {
        return new self($this->events->slice($this->positionOf($event) + 1)->values());
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
     * An opening event is open as long as it has no closing event of its own yet.
     */
    public function hasOpen(PetitionEventType $opening, PetitionEventType $closing): bool
    {
        return $this->countOfType($opening) > $this->countOfType($closing);
    }

    private function countOfType(PetitionEventType $type): int
    {
        return $this->events->filter(
            static fn(PetitionEventData $event): bool => $event->type === $type,
        )->count();
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

    private function positionOf(PetitionEventData $event): int
    {
        $position = $this->events->search(static fn(PetitionEventData $candidate): bool => $candidate->id->equals($event->id));
        Assert::integer($position, 'The event is not one of the events in the collection');

        return $position;
    }
}
