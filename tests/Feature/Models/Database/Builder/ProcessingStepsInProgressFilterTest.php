<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Builder;

use App\Enums\DecisionCriteria;
use App\Enums\ProcessingStepStatus;
use App\Models\Builder\Decision\DecisionQueryBuilder;
use App\Models\Decision;
use App\Models\Department;
use App\Models\ProcessingStep;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ProcessingStepsInProgressFilterTest extends FeatureTestCase
{
    #[Test]
    public function filterReturnsDecisionsWithProcessingStepInProgressByName(): void
    {
        $department = $this->createDepartment();

        $matchingDecision = $this->createDecisionWithProcessingStep(
            $department,
            'Matching Decision',
            'Review',
            ProcessingStepStatus::PENDING,
        );

        $this->createDecisionWithProcessingStep($department, 'Non Matching Decision', 'Approval', ProcessingStepStatus::CLOSED);

        $this->createDecisionWithProcessingStep($department, 'Different Name Decision', 'Review', ProcessingStepStatus::CLOSED);

        $results = $this->applyFilter('Review');

        $this->assertEquals(1, $results->count());
        $this->assertEquals($matchingDecision->id, $results->first()->id);
    }

    #[Test]
    public function filterReturnsEmptyWhenNoMatchingProcessingStepInProgress(): void
    {
        $department = $this->createDepartment();

        $this->createDecisionWithProcessingStep($department, 'Test Decision', 'Approval', ProcessingStepStatus::CLOSED);

        $results = $this->applyFilter('Review');

        $this->assertEquals(0, $results->count());
    }

    #[Test]
    public function filterReturnsAllDecisionsWithoutFilter(): void
    {
        $department = $this->createDepartment();

        $this->createDecisionWithProcessingStep($department, 'Decision With Pending', 'Review', ProcessingStepStatus::PENDING);

        $this->createDecisionWithProcessingStep($department, 'Decision With Closed', 'Approval', ProcessingStepStatus::CLOSED);

        $results = DecisionQueryBuilder::make()->get();

        $this->assertEquals(2, $results->count());
    }

    #[Test]
    public function filterWorksWithMultipleProcessingStepsOnDecision(): void
    {
        $department = $this->createDepartment();

        $decisionWithMatchingStep = Decision::factory()->recycle($department)->create([
            'name' => 'Multi Step Decision',
        ]);
        ProcessingStep::factory()->recycle($decisionWithMatchingStep)->create([
            'name' => 'Initial Review',
            'status' => ProcessingStepStatus::CLOSED,
        ]);
        ProcessingStep::factory()->recycle($decisionWithMatchingStep)->create([
            'name' => 'Final Review',
            'status' => ProcessingStepStatus::PENDING,
        ]);

        $this->createDecisionWithProcessingStep($department, 'All Closed Decision', 'Final Review', ProcessingStepStatus::CLOSED);

        $results = $this->applyFilter('Final Review');

        $this->assertEquals(1, $results->count());
        $this->assertEquals($decisionWithMatchingStep->id, $results->first()->id);
    }

    private function createDepartment(): Department
    {
        return Department::factory()->create();
    }

    private function createDecisionWithProcessingStep(
        Department $department,
        string $decisionName,
        string $stepName,
        ProcessingStepStatus $status,
    ): Decision {
        $decision = Decision::factory()->recycle($department)->create([
            'name' => $decisionName,
        ]);

        ProcessingStep::factory()->recycle($decision)->create([
            'name' => $stepName,
            'status' => $status,
        ]);

        return $decision;
    }

    private function applyFilter(string $stepName): Collection
    {
        $request = new Request([
            'filter' => [
                DecisionCriteria::PROCESSING_STEPS_IN_PROGRESS->value => $stepName,
            ],
        ]);

        return DecisionQueryBuilder::make($request)->get();
    }
}
