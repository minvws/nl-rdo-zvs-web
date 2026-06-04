<?php

declare(strict_types=1);

namespace App\Actions\ProcessingStep;

use App\Enums\AssignmentRole;
use App\Enums\TimelineType;
use App\Models\Decision;
use App\Models\ProcessingStep;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Webmozart\Assert\Assert;

final readonly class ProcessingStepCreateAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(Decision $decision, User $user, array $attributes): void
    {
        $this->databaseManager->transaction(static function () use ($decision, $attributes, $user): void {
            $primaryUserId = $attributes['first_assignee'] ?? null;
            $secondaryUserId = $attributes['second_assignee'] ?? null;
            unset($attributes['first_assignee']);
            unset($attributes['second_assignee']);

            $max = ProcessingStep::query()
                ->where('decision_id', $decision->id)
                ->max('ordering') ?? 0;

            Assert::integer($max);
            $attributes['ordering'] = $max + 1;

            /** @var ProcessingStep $processingStep */
            $processingStep = $decision->processingSteps()->create($attributes);

            if ($primaryUserId !== null) {
                $processingStep->assignments()->create([
                    'user_id' => $primaryUserId,
                    'assignment_role' => AssignmentRole::PRIMARY,
                ]);
            }

            if ($secondaryUserId !== null) {
                $processingStep->assignments()->create([
                    'user_id' => $secondaryUserId,
                    'assignment_role' => AssignmentRole::SECONDARY,
                ]);
            }

            $decision->timelineItems()->create([
                'type' => TimelineType::PROCESSING_STEP_CREATED,
                'user_id' => $user->id,
                'data' => new ArrayObject([
                    'name' => $processingStep->name,
                    'deadline_at' => $processingStep->deadline_at?->format('Y-m-d'),
                    'first_assignee' => $primaryUserId,
                    'status' => $processingStep->status,
                ]),
            ]);
        });
    }
}
