<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\ProcessingStep;

use App\Actions\ProcessingStep\ProcessingStepUpdateAction;
use App\Enums\ProcessingStepStatus;
use App\Models\Decision;
use App\Models\ProcessingStep;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ProcessingStepUpdateActionTest extends FeatureTestCase
{
    #[Test]
    public function updateAllFieldsSuccessfully(): void
    {
        $decision = Decision::factory()->create();
        $processingStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create([
                'name' => 'Original Name',
                'deadline_at' => CalendarDate::today(),
                'status' => ProcessingStepStatus::PENDING,
                'assigned_to' => null,
            ]);

        $user = User::factory()->create();
        $assignedUser = User::factory()->create();
        $newDeadline = CalendarDate::today()->addDays(10);

        $updateData = [
            'name' => 'Updated Name',
            'deadline_at' => $newDeadline->format('Y-m-d'),
            'status' => ProcessingStepStatus::CLOSED->value,
            'assigned_to' => $assignedUser->id,
        ];

        // Execute the action
        $action = new ProcessingStepUpdateAction($this->app->make(DatabaseManager::class));
        $action->execute($processingStep, $user, $updateData);

        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $processingStep->id,
            'name' => 'Updated Name',
            'status' => ProcessingStepStatus::CLOSED->value,
            'assigned_to' => $assignedUser->id,
        ]);
    }
}
