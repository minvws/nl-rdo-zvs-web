<?php

declare(strict_types=1);

namespace App\Actions\ProcessingStep;

use App\Enums\TimelineType;
use App\Models\Decision;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;

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
            $processingStep = $decision->processingSteps()->create($attributes);
            $decision->timelineItems()->create([
                'type' => TimelineType::PROCESSING_STEP_CREATED,
                'user_id' => $user->id,
                'data' => new ArrayObject([
                    'name' => $processingStep->name,
                    'deadline_at' => $processingStep->deadline_at?->format('Y-m-d'),
                    'assigned_to' => $processingStep->assigned_to?->toString(),
                    'status' => $processingStep->status,
                ]),
            ]);
        });
    }
}
