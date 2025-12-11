<?php

declare(strict_types=1);

namespace App\Actions\ProcessingStep;

use App\Enums\TimelineType;
use App\Models\ProcessingStep;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;

final readonly class ProcessingStepDeleteAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    public function execute(ProcessingStep $processingStep, User $user): void
    {
        $this->databaseManager->transaction(static function () use ($processingStep, $user): void {
            $processingStep->decision->timelineItems()->create([
                'type' => TimelineType::PROCESSING_STEP_DELETED,
                'user_id' => $user->id,
                'data' => new ArrayObject([
                    'name' => $processingStep->name,
                    'deadline_at' => $processingStep->deadline_at?->format('Y-m-d'),
                    'assigned_to' => $processingStep->assigned_to?->toString(),
                    'status' => $processingStep->status,
                ]),
            ]);

            $processingStep->delete();
        });
    }
}
