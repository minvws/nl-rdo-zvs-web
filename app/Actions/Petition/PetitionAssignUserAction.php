<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\AssignmentRole;
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
    public function execute(Petition $petition, User $loggedInUser, ?UuidInterface $currentAssignedUserId, AssignmentRole $assignmentRole): void
    {
        $this->databaseManager->transaction(
            static function () use ($petition, $loggedInUser, $currentAssignedUserId, $assignmentRole): void {
                $previousAssignment = $assignmentRole === AssignmentRole::PRIMARY
                    ? $petition->firstAssignee
                    : $petition->secondAssignee;

                $petition->timelineItems()->create([
                    'user_id' => $loggedInUser->id,
                    'type' => TimelineType::ASSIGNMENT_OCCURRENCE,
                    'data' => new ArrayObject([
                        'current_assigned_user_id' => $currentAssignedUserId,
                        'previous_assigned_user_id' => $previousAssignment?->user_id,
                        'assignment_role' => $assignmentRole->value,
                    ]),
                ]);

                $petition->assignments()->where('assignment_role', $assignmentRole)->delete();

                if (!($currentAssignedUserId instanceof UuidInterface)) {
                    return;
                }

                $petition->assignments()->updateOrCreate(
                    ['user_id' => $currentAssignedUserId],
                    ['assignment_role' => $assignmentRole],
                );

                /** @var User $assignedUser */
                $assignedUser = User::query()->findOrFail($currentAssignedUserId);
                $assignedUser->notify(new PetitionAssigned($petition));
            },
        );
    }
}
