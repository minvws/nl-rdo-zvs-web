<?php

declare(strict_types=1);

namespace App\Actions\PetitionEvent;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\User;
use App\ValueObjects\PetitionEventChangeData;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Throwable;

use function array_map;
use function count;

readonly class PetitionEventsPersistenceAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private UpdatePetitionTotalsFromEventsAction $updatePetitionTotalsAction,
    ) {
    }

    /**
     * @param array<int, array{type: string, date: string, duration?: int|null, penalties?: array<int, array{amount: int, duration: int}>, suspension_type?: string|null, result_type?: string|null, hearing_form?: string|null, reasoning?: string|null, withdrawal_duration?: int|null, created_at: mixed}> $petitionEventDataArray
     *
     * @throws Throwable
     */
    public function execute(Petition $petition, array $petitionEventDataArray, User $user): void
    {
        $this->databaseManager->transaction(function () use ($petition, $petitionEventDataArray, $user): void {
            $previousEvents = $petition->petitionEvents()->get();

            $petition->petitionEvents()->delete();

            $createdEvents = $petitionEventDataArray === []
                ? new EloquentCollection()
                : $petition->petitionEvents()->createMany($petitionEventDataArray);

            $petition->refresh();
            $this->updatePetitionTotalsAction->execute($petition);

            $this->createTimelineItem($petition, $petitionEventDataArray, $previousEvents, $createdEvents, $user);
        });
    }

    /**
     * @param array<int, array{type: string, date: string, duration?: int|null, penalties?: array<int, array{amount: int, duration: int}>, suspension_type?: string|null, result_type?: string|null, hearing_form?: string|null, reasoning?: string|null, withdrawal_duration?: int|null, created_at: mixed}> $petitionEventDataArray
     * @param EloquentCollection<int, PetitionEvent> $previousEvents
     * @param EloquentCollection<int, PetitionEvent> $createdEvents
     */
    private function createTimelineItem(
        Petition $petition,
        array $petitionEventDataArray,
        EloquentCollection $previousEvents,
        EloquentCollection $createdEvents,
        User $user,
    ): void {
        $events = $this->determineChanges($previousEvents, $createdEvents);

        $petition->timelineItems()->create([
            'type' => TimelineType::PETITION_EVENTS_CREATED,
            'user_id' => $user->id,
            'data' => new ArrayObject([
                'events' => array_map(static fn(PetitionEventChangeData $event): array => $event->toArray(), $events),
                'count' => count($petitionEventDataArray),
            ]),
        ]);
    }

    /**
     * Events are replaced on every save, so the previous and new events are compared on their content. Removed events
     * are placed at the position they had in the previous calendar.
     *
     * @param EloquentCollection<int, PetitionEvent> $previousEvents
     * @param EloquentCollection<int, PetitionEvent> $createdEvents
     *
     * @return array<int, PetitionEventChangeData>
     */
    private function determineChanges(EloquentCollection $previousEvents, EloquentCollection $createdEvents): array
    {
        $unmatchedPreviousEvents = $previousEvents->all();

        $matches = [];
        foreach ($createdEvents->fresh() as $event) {
            $previousIndex = $this->findMatchingEventIndex($unmatchedPreviousEvents, $event);

            if ($previousIndex !== null) {
                unset($unmatchedPreviousEvents[$previousIndex]);
            }

            $matches[] = ['event' => $event, 'previousIndex' => $previousIndex];
        }

        $changes = [];
        foreach ($matches as $match) {
            if ($match['previousIndex'] === null) {
                $changes[] = PetitionEventChangeData::createForAdded($match['event']->type);
                continue;
            }

            foreach ($unmatchedPreviousEvents as $index => $removedEvent) {
                if ($index < $match['previousIndex']) {
                    $changes[] = PetitionEventChangeData::createForRemoved($removedEvent->type);
                    unset($unmatchedPreviousEvents[$index]);
                }
            }

            $changes[] = PetitionEventChangeData::createForUnchanged($match['event']->type);
        }

        foreach ($unmatchedPreviousEvents as $removedEvent) {
            $changes[] = PetitionEventChangeData::createForRemoved($removedEvent->type);
        }

        return $changes;
    }

    /**
     * @param array<int, PetitionEvent> $candidates
     */
    private function findMatchingEventIndex(array $candidates, PetitionEvent $event): ?int
    {
        foreach ($candidates as $index => $candidate) {
            if ($candidate->hasSameContentAs($event)) {
                return $index;
            }
        }

        return null;
    }
}
