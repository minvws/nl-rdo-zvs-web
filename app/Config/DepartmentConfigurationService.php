<?php

declare(strict_types=1);

namespace App\Config;

use App\Enums\DecisionType;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Models\Department;
use Illuminate\Config\Repository;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

use function __;
use function is_array;
use function sprintf;

readonly class DepartmentConfigurationService
{
    public function __construct(
        private Repository $configRepository,
    ) {
    }

    public function createProcessingStepsOnDecisionCreation(Department $department, DecisionType $type): bool
    {
        return $this->configRepository->boolean(
            sprintf(
                'department.processing-steps.%s.%s.create_processing_steps_on_decision_creation',
                $department->slug,
                $type->value,
            ),
            false,
        );
    }

    /**
     * @return Collection<string, string>
     */
    public function processingStepOptions(Department $department, DecisionType $type): Collection
    {
        $steps = $this->configRepository->array(
            sprintf('department.processing-steps.%s.%s.options', $department->slug, $type->value),
            [],
        );
        Assert::allString($steps);

        $options = new Collection();

        foreach ($steps as $step) {
            $options->add(__('processing-step.' . $step));
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventConfiguration(
        Department $department,
        PetitionVariant $petitionType,
        PetitionEventType $eventType,
    ): array {
        $key = sprintf('petition_events.%s.%s.%s', $department->slug, $petitionType->value, $eventType->value);
        $value = $this->configRepository->get($key);

        if (is_array($value)) {
            /** @var array<string, mixed> $valueArray */
            $valueArray = $value;

            return $valueArray;
        }

        $defaultKey = sprintf('petition_events.defaults.%s.%s', $petitionType->value, $eventType->value);
        $defaultValue = $this->configRepository->get($defaultKey);

        if (is_array($defaultValue)) {
            /** @var array<string, mixed> $defaultValueArray */
            $defaultValueArray = $defaultValue;

            return $defaultValueArray;
        }

        return [];
    }
}
