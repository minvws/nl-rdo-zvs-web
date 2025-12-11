<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\PetitionPolicyDepartmentUpdateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\Petition\PetitionPolicyDepartmentUpdateRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PolicyDepartment;
use App\Models\User;
use App\View\HtmxHelper;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Throwable;

use function __;

final readonly class PetitionPolicyDepartmentController
{
    public function __construct(
        private HtmxHelper $htmxHelper,
        private Redirector $redirector,
        private Factory $view,
        private Gate $gate,
    ) {
    }

    public function edit(Request $request, Department $department, Petition $petition): Response
    {
        $this->gate->authorize(Ability::UPDATE, $petition);

        return $this->htmxHelper->makeFormViewResponse($request, 'petition.policy-department.edit', [
            'petition' => $petition,
            'policyDepartments' => PolicyDepartment::query()->active()->orderBy('name')->get(),
            'department' => $department,
        ]);
    }

    public function show(Department $department, Petition $petition): View
    {
        $this->gate->authorize(Ability::VIEW, $petition);

        return $this->view->make('petition.policy-department.show', ['petition' => $petition]);
    }

    /**
     * @throws Throwable
     */
    public function update(
        Request $request,
        Department $department,
        Petition $petition,
        PetitionPolicyDepartmentUpdateRequest $petitionPolicyDepartmentUpdateRequest,
        PetitionPolicyDepartmentUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $action->execute($petition, $user, $petitionPolicyDepartmentUpdateRequest->validated());

        if ($this->htmxHelper->isHtmxRequest($request)) {
            return $this->view->make('petition.policy-department.show', ['petition' => $petition]);
        }

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])
            ->with('message.success', __('general.saved'));
    }
}
