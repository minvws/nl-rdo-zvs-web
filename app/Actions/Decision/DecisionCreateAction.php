<?php

declare(strict_types=1);

namespace App\Actions\Decision;

use App\Config\DepartmentConfigurationService;
use App\Enums\ProcessingStepStatus;
use App\Enums\TimelineType;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Petition;
use App\Models\ProcessingStep;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Throwable;

use function array_merge;

readonly class DecisionCreateAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private DecisionAttachAction $decisionAttachAction,
        private DepartmentConfigurationService $departmentConfigurationService,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    public function execute(Department $department, User $user, array $attributes, ?Petition $petition = null): Decision
    {
        return $this->databaseManager->transaction(function () use ($petition, $user, $attributes, $department): Decision {
            /** @var Decision $decision */
            $decision = Decision::query()->create(array_merge($attributes, [
                'department_id' => $department->id,
            ]));

            $decision->timelineItems()->create([
                'user_id' => $user->id,
                'type' => TimelineType::TIMELINEABLE_CREATED,
            ]);

            if ($petition instanceof Petition) {
                $this->decisionAttachAction->execute($decision, $petition, $user);
            }

            if ($this->departmentConfigurationService->createProcessingStepsOnDecisionCreation($decision->department, $decision->type)) {
                $this->departmentConfigurationService
                    ->processingStepOptions($decision->department, $decision->type)
                    ->each(static fn (string $option): ProcessingStep => $decision->processingSteps()->create([
                        'name' => $option,
                        'status' => ProcessingStepStatus::DRAFT,
                    ]));
            }

            return $decision;
        });
    }
}
