<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\ProcessingStep;

use App\Actions\ProcessingStep\ProcessingStepMoveAction;
use App\Enums\ProcessingStepMoveDirection;
use App\Models\Decision;
use App\Models\ProcessingStep;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ProcessingStepMoveActionTest extends FeatureTestCase
{
    private ProcessingStepMoveAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new ProcessingStepMoveAction($this->app->make(DatabaseManager::class));
    }

    #[Test]
    public function moveUpSuccessfully(): void
    {
        $decision = Decision::factory()->create();

        // Create three processing steps with specific orderings
        $firstStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create(['ordering' => 1]);

        $secondStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create(['ordering' => 2]);

        $thirdStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create(['ordering' => 3]);

        // Move the third step up (should swap with second step)
        $this->action->move($thirdStep, ProcessingStepMoveDirection::UP);

        // Refresh models from database
        $thirdStep->refresh();
        $secondStep->refresh();

        // Assert orderings have been swapped
        $this->assertEquals(2, $thirdStep->ordering);
        $this->assertEquals(3, $secondStep->ordering);

        // First step should remain unchanged
        $firstStep->refresh();
        $this->assertEquals(1, $firstStep->ordering);
    }

    #[Test]
    public function moveDownSuccessfully(): void
    {
        $decision = Decision::factory()->create();

        // Create three processing steps with specific orderings
        $firstStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create(['ordering' => 1]);

        $secondStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create(['ordering' => 2]);

        $thirdStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create(['ordering' => 3]);

        // Move the first step down (should swap with second step)
        $this->action->move($firstStep, ProcessingStepMoveDirection::DOWN);

        // Refresh models from database
        $firstStep->refresh();
        $secondStep->refresh();

        // Assert orderings have been swapped
        $this->assertEquals(2, $firstStep->ordering);
        $this->assertEquals(1, $secondStep->ordering);

        // Third step should remain unchanged
        $thirdStep->refresh();
        $this->assertEquals(3, $thirdStep->ordering);
    }

    #[Test]
    public function moveUpFromTopDoesNothing(): void
    {
        $decision = Decision::factory()->create();

        $firstStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create(['ordering' => 1]);

        $secondStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create(['ordering' => 2]);

        // Attempt to move the first step up (no-op since it's already at top)
        $this->action->move($firstStep, ProcessingStepMoveDirection::UP);

        // Refresh and assert nothing changed
        $firstStep->refresh();
        $secondStep->refresh();

        $this->assertEquals(1, $firstStep->ordering);
        $this->assertEquals(2, $secondStep->ordering);
    }

    #[Test]
    public function moveDownFromBottomDoesNothing(): void
    {
        $decision = Decision::factory()->create();

        $firstStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create(['ordering' => 1]);

        $lastStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create(['ordering' => 2]);

        // Attempt to move the last step down (no-op since it's already at bottom)
        $this->action->move($lastStep, ProcessingStepMoveDirection::DOWN);

        // Refresh and assert nothing changed
        $firstStep->refresh();
        $lastStep->refresh();

        $this->assertEquals(1, $firstStep->ordering);
        $this->assertEquals(2, $lastStep->ordering);
    }

    #[Test]
    public function movePreservesOtherStepsOrdering(): void
    {
        $decision = Decision::factory()->create();

        $step1 = ProcessingStep::factory()->recycle($decision)->create(['ordering' => 1]);
        $step2 = ProcessingStep::factory()->recycle($decision)->create(['ordering' => 2]);
        $step3 = ProcessingStep::factory()->recycle($decision)->create(['ordering' => 3]);
        $step4 = ProcessingStep::factory()->recycle($decision)->create(['ordering' => 4]);

        // Move step 2 down (swap with step 3)
        $this->action->move($step2, ProcessingStepMoveDirection::DOWN);

        // Verify only step2 and step3 changed
        $step1->refresh();
        $step2->refresh();
        $step3->refresh();
        $step4->refresh();

        $this->assertEquals(1, $step1->ordering);
        $this->assertEquals(3, $step2->ordering);
        $this->assertEquals(2, $step3->ordering);
        $this->assertEquals(4, $step4->ordering);
    }
}
