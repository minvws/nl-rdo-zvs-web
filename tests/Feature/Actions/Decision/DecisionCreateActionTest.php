<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Decision;

use App\Actions\Decision\DecisionCreateAction;
use App\Enums\DecisionType;
use App\Enums\ProcessingStep as ProcessingStepEnum;
use App\Models\Department;
use App\Models\Petition;
use App\Models\ProcessingStep;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Tests\Feature\FeatureTestCase;

use function array_map;
use function range;
use function sprintf;

class DecisionCreateActionTest extends FeatureTestCase
{
    public function testCreationOfProcessingSteps(): void
    {
        $type = DecisionType::REGULAR;
        $department = Department::factory()
            ->create();
        $this->setActiveDepartment($department);
        $petition = Petition::factory()
            ->for($department)
            ->create();
        $user = User::factory()
            ->create();


        $optionsCount = $this->faker->numberBetween(1, 5);
        $options = array_map(static function (ProcessingStepEnum $processingStep): string {
            return $processingStep->value;
        }, $this->faker->randomElements(ProcessingStepEnum::cases(), $optionsCount));

        Config::set(sprintf('department.processing-steps.%s.%s', $department->slug, $type->value), [
            'create_processing_steps_on_decision_creation' => true,
            'options' => $options,
        ]);

        /** @var DecisionCreateAction $decisionCreateAction */
        $decisionCreateAction = $this->app->get(DecisionCreateAction::class);

        $decision = $decisionCreateAction->execute($department, $user, [
            'name' => $this->faker->word(),
            'type' => $type,
        ], $petition);

        $this->assertDatabaseCount(ProcessingStep::class, $optionsCount);

        $steps = $decision->processingSteps()->orderBy('ordering')->get();
        $this->assertSame(
            range(0, $optionsCount - 1),
            $steps->pluck('ordering')->all(),
            'Processing steps must have sequential ordering values starting from 0.',
        );
    }

    public function testNoCreationOfProcessingStepsIfDisabled(): void
    {
        $type = DecisionType::REGULAR;
        $department = Department::factory()
            ->create();
        $this->setActiveDepartment($department);
        $petition = Petition::factory()
            ->for($department)
            ->create();
        $user = User::factory()
            ->create();

        Config::set(sprintf('department.processing-steps.%s.%s', $department->slug, $type->value), [
            'create_processing_steps_on_decision_creation' => false,
        ]);

        /** @var DecisionCreateAction $decisionCreateAction */
        $decisionCreateAction = $this->app->get(DecisionCreateAction::class);

        $decisionCreateAction->execute($department, $user, [
            'name' => $this->faker->word(),
            'type' => $this->faker->randomElement(DecisionType::cases()),
        ], $petition);

        $this->assertDatabaseCount(ProcessingStep::class, 0);
    }

    public function testNoCreationOfProcessingStepsIfNoneConfigured(): void
    {
        $type = DecisionType::REGULAR;
        $department = Department::factory()
            ->create();
        $this->setActiveDepartment($department);
        $petition = Petition::factory()
            ->for($department)
            ->create();
        $user = User::factory()
            ->create();

        Config::set(sprintf('department.processing-steps.%s.%s', $department->slug, $type->value), [
            'create_processing_steps_on_decision_creation' => true,
            'options' => [],
        ]);

        /** @var DecisionCreateAction $decisionCreateAction */
        $decisionCreateAction = $this->app->get(DecisionCreateAction::class);

        $decisionCreateAction->execute($department, $user, [
            'name' => $this->faker->word(),
            'type' => $this->faker->randomElement(DecisionType::cases()),
        ], $petition);

        $this->assertDatabaseCount(ProcessingStep::class, 0);
    }
}
