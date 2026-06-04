<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use App\Config\DepartmentConfigurationService;
use App\Enums\DecisionType;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

use function __;
use function sprintf;

class DepartmentConfigurationServiceTest extends TestCase
{
    private DepartmentConfigurationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(DepartmentConfigurationService::class);
    }

    public function testProcessionStepOptionsReturnsCorrectOptionsForTeamA(): void
    {
        $department = Department::factory()->make(['slug' => 'team-a']);
        $type = DecisionType::REGULAR;
        $expected = new Collection([
            __('processing-step.intake'),
            __('processing-step.document-reception'),
            __('processing-step.document-assessment'),
            __('processing-step.opinion'),
            __('processing-step.decision-note'),
            __('processing-step.review'),
            __('processing-step.decision-line'),
        ]);

        $result = $this->service->processingStepOptions($department, $type);

        $this->assertEquals($expected, $result);
    }

    public function testProcessionStepOptionsReturnsCorrectOptionsForTeamB(): void
    {
        $department = Department::factory()->make(['slug' => 'team-b']);
        $type = DecisionType::REGULAR;
        $expected = new Collection([
            __('processing-step.inventory'),
            __('processing-step.assessment'),
            __('processing-step.check'),
            __('processing-step.review'),
            __('processing-step.opinion'),
            __('processing-step.decision-note'),
            __('processing-step.publish'),
        ]);

        $result = $this->service->processingStepOptions($department, $type);

        $this->assertEquals($expected, $result);
    }

    public function testProcessionStepOptionsReturnsEmptyCollectionForOtherTeams(): void
    {
        $department = Department::factory()->make(['slug' => 'other-team']);
        $type = DecisionType::REGULAR;

        $result = $this->service->processingStepOptions($department, $type);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testCreateProcessingStepsOnDecisionCreation(): void
    {
        $config = $this->faker->boolean();

        $type = DecisionType::REGULAR;
        $department = Department::factory()->make();

        Config::set(
            sprintf('department.processing-steps.%s.%s.create_processing_steps_on_decision_creation', $department->slug, $type->value),
            $config,
        );

        $this->assertEquals($config, $this->service->createProcessingStepsOnDecisionCreation($department, $type));
    }

    public function testCreateProcessingStepsOnDecisionCreationIfNotConfigured(): void
    {
        $department = Department::factory()->make();
        $type = DecisionType::REGULAR;

        $this->assertFalse($this->service->createProcessingStepsOnDecisionCreation($department, $type));
    }

    public function testGetEventConfigurationReturnsSpecificConfiguration(): void
    {
        $department = Department::factory()->make(['slug' => 'test-dept']);
        $petitionType = PetitionVariant::BEZWAAR;
        $eventType = PetitionEventType::RECEIPT_OF_OBJECTION;
        $expectedConfig = ['duration' => 10];

        Config::set(
            sprintf('petition_events.%s.%s.%s', $department->slug, $petitionType->value, $eventType->value),
            $expectedConfig,
        );

        $result = $this->service->getEventConfiguration($department, $petitionType, $eventType);

        $this->assertEquals($expectedConfig, $result);
    }

    public function testGetEventConfigurationReturnsDefaultConfiguration(): void
    {
        $department = Department::factory()->make(['slug' => 'test-dept']);
        $petitionType = PetitionVariant::BEZWAAR;
        $eventType = PetitionEventType::RECEIPT_OF_OBJECTION;
        $defaultConfig = ['duration' => 20];

        // Ensure specific config is not set
        Config::set(
            sprintf('petition_events.%s.%s.%s', $department->slug, $petitionType->value, $eventType->value),
            null,
        );

        Config::set(
            sprintf('petition_events.defaults.%s.%s', $petitionType->value, $eventType->value),
            $defaultConfig,
        );

        $result = $this->service->getEventConfiguration($department, $petitionType, $eventType);

        $this->assertEquals($defaultConfig, $result);
    }

    public function testGetEventConfigurationReturnsEmptyArrayWhenNotConfigured(): void
    {
        $department = Department::factory()->make(['slug' => 'test-dept']);
        $petitionType = PetitionVariant::BEZWAAR;
        $eventType = PetitionEventType::RECEIPT_OF_OBJECTION;

        // Ensure neither specific nor default config is set
        Config::set(
            sprintf('petition_events.%s.%s.%s', $department->slug, $petitionType->value, $eventType->value),
            null,
        );
        Config::set(
            sprintf('petition_events.defaults.%s.%s', $petitionType->value, $eventType->value),
            null,
        );

        $result = $this->service->getEventConfiguration($department, $petitionType, $eventType);

        $this->assertEquals([], $result);
    }
}
