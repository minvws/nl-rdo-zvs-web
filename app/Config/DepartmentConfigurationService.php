<?php

declare(strict_types=1);

namespace App\Config;

use App\Enums\DecisionType;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Enums\ResultType;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Models\Department;
use Illuminate\Config\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Webmozart\Assert\Assert;

use function __;
use function in_array;
use function is_array;
use function is_string;
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

    public function unspecifiedAdjournmentMode(Department $department): UnspecifiedAdjournmentMode
    {
        $mode = $this->configRepository->get(
            sprintf('department.unspecified-adjournment.%s', $department->slug),
        );

        if (!is_string($mode)) {
            return UnspecifiedAdjournmentMode::CONTINUES_TERMS;
        }

        return UnspecifiedAdjournmentMode::tryFrom($mode) ?? UnspecifiedAdjournmentMode::CONTINUES_TERMS;
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

    public function resultTypeLabel(Department $department, ResultType $resultType): string
    {
        $key = sprintf('result_type.%s.%s', $department->slug, $resultType->value);

        if (!Lang::has($key)) {
            return $resultType->label();
        }

        $label = Lang::get($key);
        Assert::string($label);

        return $label;
    }

    /**
     * Bepaalt of een gebeurtenistype beschikbaar is voor een team
     *
     * Zie config/petition_events.php (department_availability).
     * Een gebeurtenistype dat daar niet in staat is voor elk team beschikbaar,
     * staat het er wel in, dan mogen alleen de genoemde teams het invoeren.
     */
    public function isEventTypeAvailable(Department $department, PetitionEventType $eventType): bool
    {
        $allowed = $this->configRepository->get(
            sprintf('petition_events.department_availability.%s', $eventType->value),
        );

        if (!is_array($allowed)) {
            return true;
        }

        Assert::allString($allowed);

        return in_array($department->slug, $allowed, true);
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
