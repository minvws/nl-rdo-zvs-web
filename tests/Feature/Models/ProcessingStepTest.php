<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\ProcessingStepStatus;
use App\Models\Decision;
use App\Models\ProcessingStep;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ProcessingStepTest extends FeatureTestCase
{
    #[Test]
    public function testAssignedUser(): void
    {
        $user = User::factory()->create();

        $processingStep = $user->assignedProcessingSteps()->create([
            'name' => 'Test Processing Step',
            'decision_id' => Decision::factory()->create()->id,
            'status' => ProcessingStepStatus::PENDING,
        ]);

        $this->assertInstanceOf(ProcessingStep::class, $processingStep);
        $this->assertEquals($user->id, $processingStep->assigned_to);
        $this->assertEquals($user->id, $processingStep->assignedUser->id);
    }
}
