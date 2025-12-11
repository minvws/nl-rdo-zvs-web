<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Terms\PetitionTermCreateAction;
use App\Actions\Terms\PetitionTermDeleteAction;
use App\Actions\Terms\PetitionTermUpdateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Enums\TermType;
use App\Http\Requests\Petition\PetitionTermCreateRequest;
use App\Http\Requests\Petition\PetitionTermUpdateRequest;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\Factory;

use function __;

final readonly class PetitionTermController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
        private Gate $gate,
    ) {
    }

    public function create(Department $department, Petition $petition, TermType $termType): View
    {
        $this->gate->authorize(Ability::UPDATE, $petition);

        return $this->view->make('petition.petition-terms.create', [
            'department' => $department,
            'petition' => $petition,
            'termType' => $termType,
            'departmentTermTypeSettings' => DepartmentTermTypeSetting::whereDepartmentAndType($department, $termType)->get(),
        ]);
    }

    public function store(
        PetitionTermCreateRequest $request,
        Department $department,
        Petition $petition,
        TermType $termType,
        PetitionTermCreateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $action->execute($petition, $termType, $user, $request->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])
            ->with('message.success', __('general.saved'));
    }

    public function edit(Department $department, Petition $petition, PetitionTerm $petitionTerm): View
    {
        $this->gate->authorize(Ability::UPDATE, $petition);

        return $this->view->make('petition.petition-terms.edit', [
            'department' => $department,
            'petition' => $petition,
            'term' => $petitionTerm,
            'departmentTermTypeSettings' => DepartmentTermTypeSetting::whereDepartmentAndType($department, $petitionTerm->type)->get(),
        ]);
    }

    public function update(
        PetitionTermUpdateRequest $request,
        Department $department,
        Petition $petition,
        PetitionTerm $petitionTerm,
        PetitionTermUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $action->execute($department, $petitionTerm, $user, $request->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])->with('message.success', __('general.saved'));
    }

    public function delete(
        Department $department,
        Petition $petition,
        PetitionTerm $petitionTerm,
        PetitionTermDeleteAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $action->execute($petition, $petitionTerm, $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])->with('message.success', __('general.deleted'));
    }
}
