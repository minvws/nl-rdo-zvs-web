<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\PetitionAssignUserAction;
use App\Enums\Ability;
use App\Enums\AssignmentRole;
use App\Enums\RouteName;
use App\Http\Requests\Petition\PetitionAssignedSecondaryUserRequest;
use App\Http\Requests\Petition\PetitionAssignedUserRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\Repositories\RepositoryTransactionException;
use App\View\HtmxHelper;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;

use function __;

final readonly class PetitionAssignedUserController
{
    public function __construct(
        private Redirector $redirector,
        private Factory $view,
        private HtmxHelper $htmxHelper,
    ) {
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function editAssignedUser(Request $request, Department $department, Petition $petition): Response
    {
        return $this->htmxHelper->makeFormViewResponse($request, 'petition.assign-primary.edit', [
            'petition' => $petition,
            'users' => User::query()
                ->getUsersWithWriteAccessOnDepartment($department)
                ->active()
                ->get(),
            'department' => $department,
        ]);
    }

    /**
     * @throws RepositoryTransactionException
     */
    #[Authorize(Ability::UPDATE, 'petition')]
    public function updateAssignedUser(
        Department $department,
        Petition $petition,
        PetitionAssignedUserRequest $petitionAssignedUserRequest,
        PetitionAssignUserAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View {
        $action->execute($petition, $user, $petitionAssignedUserRequest->getUuidOrNull('user_id'), AssignmentRole::PRIMARY);

        $petition->refresh()->load([
            'department',
            'firstAssignee.user',
        ]);


        if ($this->htmxHelper->isHtmxRequest($petitionAssignedUserRequest)) {
            return $this->view->make('petition.assign-primary.show', [
                'petition' => $petition,
                'department' => $department,
            ]);
        }

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])
            ->with('message.success', __('general.saved'));
    }

    #[Authorize(Ability::VIEW, 'petition')]
    public function showAssignedUser(Department $department, Petition $petition): View
    {
        return $this->view->make('petition.assign-primary.show', [
            'petition' => $petition,
            'department' => $department,
        ]);
    }

    #[Authorize(Ability::VIEW, 'petition')]
    public function showAssignedSecondaryUser(Department $department, Petition $petition): View
    {
        return $this->view->make('petition.assign-secondary.show', [
            'petition' => $petition,
            'department' => $department,
        ]);
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function editAssignedSecondaryUser(Request $request, Department $department, Petition $petition): Response
    {
        return $this->htmxHelper->makeFormViewResponse($request, 'petition.assign-secondary.edit', [
            'petition' => $petition,
            'users' => User::query()
                ->getUsersWithWriteAccessOnDepartment($department)
                ->active()
                ->get(),
            'department' => $department,
        ]);
    }

    /**
     * @throws RepositoryTransactionException
     */
    #[Authorize(Ability::UPDATE, 'petition')]
    public function updateAssignedSecondaryUser(
        Department $department,
        Petition $petition,
        PetitionAssignedSecondaryUserRequest $request,
        PetitionAssignUserAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View {
        $action->execute($petition, $user, $request->getUuidOrNull('user_id'), AssignmentRole::SECONDARY);

        $petition->refresh()->load([
            'department',
            'firstAssignee.user',
            'secondAssignee.user',
        ]);

        if ($this->htmxHelper->isHtmxRequest($request)) {
            return $this->view->make('petition.assign-secondary.show', [
                'petition' => $petition,
                'department' => $department,
            ]);
        }

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])
            ->with('message.success', __('general.saved'));
    }
}
