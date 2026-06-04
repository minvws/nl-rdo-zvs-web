<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\PetitionAttachAction;
use App\Actions\Petition\PetitionDetachAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\Petition\PetitionAttachRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\Services\Petition\PetitionException;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Illuminate\View\Factory;
use Throwable;

final readonly class PetitionAttachController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
    ) {
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function attachForm(Department $department, Petition $petition): View
    {
        return $this->view->make('petition.petition.attach', [
            'petition' => $petition,
            'department' => $department,
        ]);
    }

    /**
     * @throws Throwable
     */
    #[Authorize(Ability::UPDATE, 'petition')]
    public function attachPetitionToPetition(
        PetitionAttachRequest $petitionAttachRequest,
        Department $department,
        Petition $petition,
        PetitionAttachAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $action->execute($petition, $user, $petitionAttachRequest->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]);
    }

    /**
     * @throws PetitionException
     */
    #[Authorize(Ability::UPDATE, 'petition')]
    public function detachPetitionFromPetition(
        Department $department,
        Petition $petition,
        Petition $relatedPetition,
        PetitionDetachAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $action->execute($petition, $relatedPetition, $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]);
    }
}
