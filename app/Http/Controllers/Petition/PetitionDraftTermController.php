<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Terms\PetitionDraftTermCreateAction;
use App\Actions\Terms\PetitionDraftTermDeleteAction;
use App\Actions\Terms\PetitionDraftTermUpdateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\Petition\PetitionDraftTermCreateRequest;
use App\Http\Requests\Petition\PetitionDraftTermUpdateRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Illuminate\View\Factory;

use function __;

final readonly class PetitionDraftTermController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
    ) {
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function create(Department $department, Petition $petition): View
    {
        return $this->view->make('petition.draft-term.create', [
            'petition' => $petition,
        ]);
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function store(
        PetitionDraftTermCreateRequest $request,
        Department $department,
        Petition $petition,
        PetitionDraftTermCreateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $action->execute($petition, $user, $request->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])
            ->with('message.success', __('general.saved'));
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function edit(Department $department, Petition $petition): View|RedirectResponse
    {
        $draftTerm = $petition->draftTerm;

        if (!$draftTerm) {
            return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_CREATE, [
                'department' => $department,
                'petition' => $petition,
            ]);
        }

        return $this->view->make('petition.draft-term.edit', [
            'petition' => $petition,
            'draftTerm' => $draftTerm,
        ]);
    }

    public function update(
        PetitionDraftTermUpdateRequest $request,
        Department $department,
        Petition $petition,
        PetitionDraftTermUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $draftTerm = $petition->draftTerm;

        if (!$draftTerm) {
            return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_CREATE, [
                'department' => $department,
                'petition' => $petition,
            ]);
        }

        $action->execute($petition, $draftTerm, $user, $request->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])->with('message.success', __('general.saved'));
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function delete(
        Department $department,
        Petition $petition,
        PetitionDraftTermDeleteAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $draftTerm = $petition->draftTerm;

        if ($draftTerm) {
            $action->execute($petition, $draftTerm, $user);
        }

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])->with('message.success', __('general.deleted'));
    }
}
