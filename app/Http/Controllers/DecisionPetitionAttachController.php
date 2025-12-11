<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Decision\DecisionAttachAction;
use App\Actions\Decision\DecisionDetachAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\Petition\PetitionAttachRequest;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\Factory;

final readonly class DecisionPetitionAttachController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
        private Gate $gate,
    ) {
    }

    public function attachForm(Department $department, Decision $decision): View
    {
        $this->gate->authorize(Ability::UPDATE, $decision);

        return $this->view->make('petition.decision.petition-attach', [
            'decision' => $decision,
        ]);
    }

    public function attachPetitionToDecision(
        PetitionAttachRequest $petitionAttachRequest,
        Department $department,
        Decision $decision,
        DecisionAttachAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $this->gate->authorize(Ability::UPDATE, $decision);

        $petition = Petition::query()
            ->where('number', $petitionAttachRequest->getString('number'))
            ->firstOrFail();

        $action->execute($decision, $petition, $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
            'department' => $department,
            'decision' => $decision,
        ]);
    }

    public function detachPetitionFromDecision(
        Department $department,
        Decision $decision,
        Petition $relatedPetition,
        DecisionDetachAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $this->gate->authorize(Ability::UPDATE, $decision);

        $action->execute($decision, $relatedPetition, $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
            'department' => $department,
            'decision' => $decision,
        ]);
    }
}
