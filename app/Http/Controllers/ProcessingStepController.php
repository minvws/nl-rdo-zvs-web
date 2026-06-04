<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProcessingStep\ProcessingStepCreateAction;
use App\Actions\ProcessingStep\ProcessingStepDeleteAction;
use App\Actions\ProcessingStep\ProcessingStepMoveAction;
use App\Actions\ProcessingStep\ProcessingStepUpdateAction;
use App\Config\DepartmentConfigurationService;
use App\Enums\Ability;
use App\Enums\ProcessingStepMoveDirection;
use App\Enums\ProcessingStepStatus;
use App\Enums\RouteName;
use App\Http\Requests\ProcessingStepCreateRequest;
use App\Http\Requests\ProcessingStepUpdateRequest;
use App\Models\Decision;
use App\Models\Department;
use App\Models\ProcessingStep;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Illuminate\View\Factory;
use Webmozart\Assert\Assert;

use function __;

final readonly class ProcessingStepController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
        private DepartmentConfigurationService $configurationService,
    ) {
    }

    #[Authorize(Ability::UPDATE, 'decision')]
    public function create(
        Department $department,
        Decision $decision,
    ): View {
        $users = User::query()
            ->getUsersWithWriteAccessOnDepartment($department)
            ->pluck('name', 'id');

        $options = $this->configurationService->processingStepOptions($department, $decision->type);

        $defaultOrdering = $decision->processingSteps()->max('ordering') ?? 0;
        Assert::integer($defaultOrdering);

        return $this->view->make('departments.decisions.processing-steps.create', [
            'options' => $options,
            'department' => $department,
            'decision' => $decision,
            'users' => $users,
            'statuses' => ProcessingStepStatus::cases(),
            'defaultOrdering' => $defaultOrdering,
        ]);
    }

    #[Authorize(Ability::UPDATE, 'decision')]
    public function store(
        Department $department,
        Decision $decision,
        ProcessingStepCreateRequest $request,
        ProcessingStepCreateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $action->execute($decision, $user, $request->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
            'department' => $department,
            'decision' => $decision,
        ])->with('message.success', __('general.saved'));
    }

    #[Authorize(Ability::UPDATE, 'decision')]
    public function edit(
        Department $department,
        Decision $decision,
        ProcessingStep $processingStep,
    ): View {
        $users = User::query()
            ->getUsersWithWriteAccessOnDepartment($department)
            ->pluck('name', 'id');

        return $this->view->make('departments.decisions.processing-steps.edit', [
            'options' => $this->configurationService->processingStepOptions($department, $decision->type),
            'department' => $department,
            'decision' => $decision,
            'processingStep' => $processingStep,
            'users' => $users,
            'statuses' => ProcessingStepStatus::cases(),
        ]);
    }

    #[Authorize(Ability::UPDATE, 'decision')]
    public function update(
        Department $department,
        Decision $decision,
        ProcessingStep $processingStep,
        ProcessingStepUpdateRequest $request,
        ProcessingStepUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $action->execute($processingStep, $user, $request->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
            'department' => $department,
            'decision' => $decision,
        ])->with('message.success', __('general.saved'));
    }

    #[Authorize(Ability::UPDATE, 'decision')]
    public function move(
        Department $department,
        Decision $decision,
        ProcessingStep $processingStep,
        ProcessingStepMoveAction $action,
        ProcessingStepMoveDirection $direction,
    ): RedirectResponse {
        $action->move($processingStep, $direction);

        return $this->redirector->route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
            'department' => $department,
            'decision' => $decision,
        ]);
    }

    #[Authorize(Ability::UPDATE, 'decision')]
    public function delete(
        Department $department,
        Decision $decision,
        ProcessingStep $processingStep,
        ProcessingStepDeleteAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $action->execute($processingStep, $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
            'department' => $department,
            'decision' => $decision,
        ])->with('message.success', __('general.deleted'));
    }
}
