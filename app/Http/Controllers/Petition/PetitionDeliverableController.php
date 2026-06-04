<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Terms\PetitionDeliverableCreateAction;
use App\Actions\Terms\PetitionDeliverableDeleteAction;
use App\Actions\Terms\PetitionDeliverableUpdateAction;
use App\Enums\Ability;
use App\Enums\PetitionDeliverableType;
use App\Enums\RouteName;
use App\Http\Requests\Petition\PetitionDeliverableCreateRequest;
use App\Http\Requests\Petition\PetitionDeliverableUpdateRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionDeliverable;
use App\Models\User;
use App\Repositories\RepositoryTransactionException;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Illuminate\View\Factory;

use function __;

final readonly class PetitionDeliverableController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
    ) {
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function create(Department $department, Petition $petition, PetitionDeliverableType $petitionDeliverableType): View
    {
        return $this->view->make('petition.petition-deliverable.create', [
            'petition' => $petition,
            'petitionDeliverableType' => $petitionDeliverableType,
        ]);
    }

    /**
     * @throws RepositoryTransactionException
     */
    #[Authorize(Ability::UPDATE, 'petition')]
    public function store(
        PetitionDeliverableCreateRequest $request,
        Department $department,
        Petition $petition,
        PetitionDeliverableType $petitionDeliverableType,
        PetitionDeliverableCreateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $action->execute($petition, $petitionDeliverableType, $request->validated(), $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])->with('message.success', __('general.saved'));
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function edit(Department $department, Petition $petition, PetitionDeliverable $petitionDeliverable): View
    {
        return $this->view->make('petition.petition-deliverable.edit', [
            'petition' => $petition,
            'petitionDeliverable' => $petitionDeliverable,
        ]);
    }

    /**
     * @throws RepositoryTransactionException
     */
    #[Authorize(Ability::UPDATE, 'petition')]
    public function update(
        PetitionDeliverableUpdateRequest $request,
        Department $department,
        Petition $petition,
        PetitionDeliverable $petitionDeliverable,
        PetitionDeliverableUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $action->execute($petitionDeliverable, $request->validated(), $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])->with('message.success', __('general.saved'));
    }

    /**
     * @throws RepositoryTransactionException
     */
    #[Authorize(Ability::UPDATE, 'petition')]
    public function delete(
        Department $department,
        Petition $petition,
        PetitionDeliverable $petitionDeliverable,
        PetitionDeliverableDeleteAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $action->execute($petition, $petitionDeliverable, $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])->with('message.success', __('general.deleted'));
    }
}
