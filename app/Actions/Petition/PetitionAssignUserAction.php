<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use App\Notifications\PetitionAssigned;
use App\Repositories\RepositoryTransactionException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Ramsey\Uuid\UuidInterface;

readonly class PetitionAssignUserAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @throws RepositoryTransactionException
     */
    public function execute(Petition $petition, User $loggedInUser, ?UuidInterface $currentAssignedUserId): void
    {
        $this->databaseManager->transaction(
            static function () use ($petition, $loggedInUser, $currentAssignedUserId): void {
                $petition->timelineItems()->create([
                    'user_id' => $loggedInUser->id,
                    'type' => TimelineType::ASSIGNMENT_OCCURRENCE,
                    'data' => new ArrayObject([
                        'current_assigned_user_id' => $currentAssignedUserId,
                        'previous_assigned_user_id' => $petition->assigned_to,
                    ]),
                ]);

                $petition->assigned_to = $currentAssignedUserId;
                $petition->save();

                if (!$currentAssignedUserId instanceof UuidInterface) {
                    return;
                }

                /** @var User $assignedUser */
                $assignedUser = User::query()->findOrFail($currentAssignedUserId);

                $assignedUser->notify(new PetitionAssigned($petition));
            },
        );
    }
}
