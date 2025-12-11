<?php

declare(strict_types=1);

namespace App\Actions\Terms;

use App\Enums\PetitionDeliverableType;
use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\PetitionDeliverable;
use App\Models\User;
use App\Repositories\DatabaseRepositoryTransaction;
use App\Repositories\RepositoryTransactionException;
use Illuminate\Database\Eloquent\Casts\ArrayObject;

use function array_merge;

readonly class PetitionDeliverableCreateAction
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
    public function execute(Petition $petition, PetitionDeliverableType $petitionDeliverableType, array $attributes, User $user): void
    {
        $petitionDeliverableAttributes = array_merge($attributes, [

            'petition_id' => $petition->id,
            'type' => $petitionDeliverableType,
        ]);

        $this->databaseRepositoryTransaction->transaction(
            function () use ($petition, $petitionDeliverableAttributes, $user, $petitionDeliverableType): void {
                PetitionDeliverable::query()->create($petitionDeliverableAttributes);

                $petition->timelineItems()->create([
                    'type' => TimelineType::DELIVERABLE_CREATED,
                    'user_id' => $user->id,
                    'data' => new ArrayObject([
                        'type' => $petitionDeliverableType->value,
                    ]),
                ]);

                $this->deadlineAtAction->execute($petition);
            },
        );
    }
}
