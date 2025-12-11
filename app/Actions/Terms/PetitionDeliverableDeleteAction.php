<?php

declare(strict_types=1);

namespace App\Actions\Terms;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\PetitionDeliverable;
use App\Models\User;
use App\Repositories\DatabaseRepositoryTransaction;
use App\Repositories\RepositoryTransactionException;
use Illuminate\Database\Eloquent\Casts\ArrayObject;

readonly class PetitionDeliverableDeleteAction
{
    public function __construct(
        private DatabaseRepositoryTransaction $databaseRepositoryTransaction,
        private PetitionDeliverableUpdatePetitionDeadlineAtAction $deadlineAtAction,
    ) {
    }

    /**
     * @throws RepositoryTransactionException
     */
    public function execute(Petition $petition, PetitionDeliverable $petitionDeliverable, User $user): void
    {
        $this->databaseRepositoryTransaction->transaction(function () use ($petition, $petitionDeliverable, $user): void {
            $petition->timelineItems()->create([
                'type' => TimelineType::DELIVERABLE_DELETED,
                'user_id' => $user->id,
                'data' => new ArrayObject([
                    'type' => $petitionDeliverable->type,
                ]),
            ]);

            $petitionDeliverable->delete();

            $this->deadlineAtAction->execute($petition);
        });
    }
}
