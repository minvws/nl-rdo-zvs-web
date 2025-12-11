<?php

declare(strict_types=1);

namespace App\Actions\ProcessingStep;

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
            $processingStep->update($attributes);
            $processingStep->refresh();

            $processingStep->decision->timelineItems()->create([
                'type' => TimelineType::PROCESSING_STEP_UPDATED,
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
