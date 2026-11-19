<?php

declare(strict_types=1);

namespace App\Actions\Petition;

use App\Enums\AssignmentRole;
use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\User;
use App\Repositories\RepositoryTransactionException;
use App\Services\NotificationRule\NotificationRuleEvaluator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\UuidInterface;
use Throwable;

use function sprintf;

readonly class PetitionAssignUserAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private NotificationRuleEvaluator $notificationRuleEvaluator,
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
            },
        );

        if ($assignmentRole === AssignmentRole::PRIMARY) {
            $this->evaluateNotificationRules($petition);
        }
    }

    /**
     * A new primary assignee gets the notifications that are already due, instead of waiting for the daily job. The
     * assignment is saved by now, so a failing notification must not fail it; the daily job tries again.
     */
    private function evaluateNotificationRules(Petition $petition): void
    {
        try {
            $this->notificationRuleEvaluator->evaluate($petition->refresh());
        } catch (Throwable $throwable) {
            Log::error(sprintf('Failed to evaluate notification rules for petition ID: %s', $petition->id), [
                'exception' => $throwable,
            ]);
        }
    }
}
