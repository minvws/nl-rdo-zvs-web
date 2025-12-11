<?php

declare(strict_types=1);

namespace Tests\Feature\Collections;

use App\Collections\ProcessingStepCollection;
use App\Enums\ProcessingStepStatus;
use App\Models\ProcessingStep;
use Carbon\Carbon;
use Tests\Feature\FeatureTestCase;

class ProcessingStepCollectionTest extends FeatureTestCase
{
    public function testDeadlineIsLowestDeadline(): void
    {
        $knownDate = Carbon::create(2023, 9, 30);
        Carbon::setTestNow($knownDate);

        $collection = new ProcessingStepCollection([
            new ProcessingStep(['deadline_at' => '2023-10-03']),
            new ProcessingStep(['deadline_at' => '2023-10-02']),
            new ProcessingStep(['deadline_at' => '2023-10-01']),
            new ProcessingStep(['deadline_at' => '2023-10-04']),
        ]);

        $this->assertEquals('2023-10-01', $collection->deadline()->format('Y-m-d'));
    }

    public function testNoDeadline(): void
    {
        $collection = new ProcessingStepCollection([]);

        $this->assertNull($collection->deadline());
    }

    public function testDeadlineIsNull(): void
    {
        $collection = new ProcessingStepCollection([
            new ProcessingStep(['deadline_at' => null]),
        ]);

        $this->assertNull($collection->deadline());
    }

    public function testDeadlineIsLowestFutureDeadline(): void
    {
        $knownDate = Carbon::create(2023, 10, 02);
        Carbon::setTestNow($knownDate);

        $collection = new ProcessingStepCollection([
            new ProcessingStep(['deadline_at' => '2023-10-03']),
            new ProcessingStep(['deadline_at' => '2023-10-02']),
            new ProcessingStep(['deadline_at' => '2023-10-01']),
            new ProcessingStep(['deadline_at' => '2023-10-04']),
        ]);

        $this->assertEquals('2023-10-02', $collection->deadline()->format('Y-m-d'));
    }

    public function testCalculateTotal(): void
    {
        $decisionSteps = ProcessingStep::factory()->count(4)->make();
        $collection = new ProcessingStepCollection($decisionSteps);

        $this->assertEquals(4, $collection->countTotal());
    }

    public function testCalculateCompleted(): void
    {
        $decisionSteps1 = ProcessingStep::factory()->count(4)->make([
            'status' => ProcessingStepStatus::CLOSED,
        ]);
        $decisionSteps2 = ProcessingStep::factory()->count(4)->make([
            'status' => ProcessingStepStatus::PENDING,
        ]);
        $collection = new ProcessingStepCollection([...$decisionSteps1, ...$decisionSteps2]);

        $this->assertEquals(4, $collection->countCompleted());
    }
}
