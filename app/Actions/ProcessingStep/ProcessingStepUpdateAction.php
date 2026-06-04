<?php

declare(strict_types=1);

namespace App\Actions\ProcessingStep;

use App\Enums\AssignmentRole;
use App\Enums\TimelineType;
use App\Models\ProcessingStep;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;

final readonly class ProcessingStepUpdateAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(ProcessingStep $processingStep, User $user, array $attributes): void
    {
        $this->databaseManager->transaction(static function () use ($processingStep, $user, $attributes): void {
            $primaryUserId = $attributes['first_assignee'] ?? null;
            $secondaryUserId = $attributes['second_assignee'] ?? null;
            unset($attributes['second_assignee']);
            unset($attributes['first_assignee']);

            $processingStep->update($attributes);
            $processingStep->refresh();

            $processingStep->assignments()->where('assignment_role', AssignmentRole::PRIMARY)->delete();
            if ($primaryUserId !== null) {
                $processingStep->assignments()->updateOrCreate(
                    ['assignment_role' => AssignmentRole::PRIMARY],
                    ['user_id' => $primaryUserId],
                );
            }

            $processingStep->assignments()->where('assignment_role', AssignmentRole::SECONDARY)->delete();
            if ($secondaryUserId !== null) {
                $processingStep->assignments()->updateOrCreate(
                    ['assignment_role' => AssignmentRole::SECONDARY],
                    ['user_id' => $secondaryUserId],
                );
            }

            $processingStep->decision->timelineItems()->create([
                'type' => TimelineType::PROCESSING_STEP_UPDATED,
                'user_id' => $user->id,
                'data' => new ArrayObject([
                    'name' => $processingStep->name,
                    'deadline_at' => $processingStep->deadline_at?->format('Y-m-d'),
                    'first_assignee' => $processingStep->firstAssignee?->user->id->toString(),
                    'status' => $processingStep->status,
                ]),
            ]);
        });
    }
}
