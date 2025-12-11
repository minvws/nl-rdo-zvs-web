<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\ProcessingStep;

use App\Actions\ProcessingStep\ProcessingStepCreateAction;
use App\Enums\ProcessingStepStatus;
use App\Models\Decision;
use App\Models\ProcessingStep;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ProcessingStepCreateActionTest extends FeatureTestCase
{
    #[Test]
    public function createActionTest(): void
    {
        $decision = Decision::factory()->create();
        $user = User::factory()->create();
        $assignedUser = User::factory()->create();
        $deadlineDate = CalendarDate::today()->addDays(14);

        $data = [
            'name' => 'Complete Processing Step',
            'deadline_at' => $deadlineDate->format('Y-m-d'),
            'status' => ProcessingStepStatus::CLOSED->value,
            'assigned_to' => $assignedUser->id,
        ];

        $action = new ProcessingStepCreateAction(
            $this->app->make(DatabaseManager::class),
        );
        $action->execute($decision, $user, $data);

        $this->assertDatabaseHas(ProcessingStep::class, [
            'name' => 'Complete Processing Step',
            'decision_id' => $decision->id,
            'assigned_to' => $assignedUser->id,
            'status' => ProcessingStepStatus::CLOSED->value,
        ]);
    }
}
