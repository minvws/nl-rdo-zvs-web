<?php

declare(strict_types=1);

namespace App\Actions\Terms;

use App\Enums\TimelineType;
use App\Models\PetitionDeliverable;
use App\Models\User;
use App\Repositories\DatabaseRepositoryTransaction;
use App\Repositories\RepositoryTransactionException;
use Illuminate\Database\Eloquent\Casts\ArrayObject;

readonly class PetitionDeliverableUpdateAction
{
    public function __construct(
        private DatabaseRepositoryTransaction $databaseRepositoryTransaction,
        private PetitionDeliverableUpdatePetitionDeadlineAtAction $deadlineAtAction,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws RepositoryTransactionException
     */
    public function execute(PetitionDeliverable $petitionDeliverable, array $attributes, User $user): void
    {
        $this->databaseRepositoryTransaction->transaction(function () use ($petitionDeliverable, $attributes, $user): void {
            $petitionDeliverable->update($attributes);

            $petitionDeliverable->petition->timelineItems()->create([
                'type' => TimelineType::DELIVERABLE_UPDATED,
                'user_id' => $user->id,
                'data' => new ArrayObject([
                    'type' => $petitionDeliverable->type,
                ]),
            ]);

            $this->deadlineAtAction->execute($petitionDeliverable->petition);
        });
    }
}
