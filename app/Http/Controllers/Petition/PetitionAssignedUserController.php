<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\PetitionAssignUserAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\Petition\PetitionAssignedUserRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\Repositories\RepositoryTransactionException;
use App\View\HtmxHelper;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;

use function __;

final readonly class PetitionAssignedUserController
{
    public function __construct(
        private Redirector $redirector,
        private Factory $view,
        private HtmxHelper $htmxHelper,
        private Gate $gate,
    ) {
    }

    public function editAssignedUser(Request $request, Department $department, Petition $petition): Response
    {
        $this->gate->authorize(Ability::UPDATE, $petition);

        return $this->htmxHelper->makeFormViewResponse($request, 'petition.assign-user.edit', [
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
    public function updateAssignedUser(
        Department $department,
        Petition $petition,
        PetitionAssignedUserRequest $petitionAssignedUserRequest,
        PetitionAssignUserAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $action->execute($petition, $user, $petitionAssignedUserRequest->getUuidOrNull('user_id'));


        if ($this->htmxHelper->isHtmxRequest($petitionAssignedUserRequest)) {
            return $this->view->make('petition.assign-user.show', [
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

    public function showAssignedUser(Department $department, Petition $petition): View
    {
        $this->gate->authorize(Ability::VIEW, $petition);

        return $this->view->make('petition.assign-user.show', [
            'petition' => $petition,
            'department' => $department,
        ]);
    }
}
