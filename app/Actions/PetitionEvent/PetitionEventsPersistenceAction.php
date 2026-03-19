<?php

declare(strict_types=1);

namespace App\Actions\PetitionEvent;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
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
     * @param array<int, array{type: string, date: string, duration?: int|null, penalties?: array<int, array{amount: int, duration: int}>, suspension_type?: string|null, result_type?: string|null, created_at: mixed}> $petitionEventDataArray
     *
     * @throws Throwable
     */
    public function execute(Petition $petition, array $petitionEventDataArray, User $user): void
    {
        $this->databaseManager->transaction(function () use ($petition, $petitionEventDataArray, $user): void {
            $petition->petitionEvents()->delete();

            if ($petitionEventDataArray !== []) {
                $petition->petitionEvents()->createMany($petitionEventDataArray);
            }

            $petition->refresh();
            $this->updatePetitionTotalsAction->execute($petition);

            $this->createTimelineItem($petition, $petitionEventDataArray, $user);
        });
    }

    /**
     * @param array<int, array{type: string, date: string, duration?: int|null, penalties?: array<int, array{amount: int, duration: int}>, suspension_type?: string|null, result_type?: string|null, created_at: mixed}> $petitionEventDataArray
     */
    private function createTimelineItem(Petition $petition, array $petitionEventDataArray, User $user): void
    {
        $eventTypes = array_map(static fn(array $eventData): string => $eventData['type'], $petitionEventDataArray);

        $petition->timelineItems()->create([
            'type' => TimelineType::PETITION_EVENTS_CREATED,
            'user_id' => $user->id,
            'data' => new ArrayObject([
                'event_types' => $eventTypes,
                'count' => count($petitionEventDataArray),
            ]),
        ]);
    }
}
