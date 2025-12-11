<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Decision\DecisionAttachAction;
use App\Actions\Decision\DecisionDetachAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\DecisionAttachRequest;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\Factory;

final readonly class DecisionAttachController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
        private Gate $gate,
    ) {
    }

    public function attachForm(Department $department, Petition $petition): View
    {
        $this->gate->authorize(Ability::UPDATE, $petition);

        return $this->view->make('petition.decision.attach', [
            'department' => $department,
            'petition' => $petition,
        ]);
    }

    public function attachDecisionToPetition(
        Department $department,
        Petition $petition,
        DecisionAttachRequest $request,
        DecisionAttachAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $decision = Decision::query()

            ->where('reference', $request->getString('reference'))
            ->firstOrFail();

        $action->execute($decision, $petition, $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]);
    }

    public function detachDecisionFromPetition(
        Department $department,
        Petition $petition,
        Decision $relatedDecision,
        DecisionDetachAction $action,
        Request $request,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $action->execute($relatedDecision, $petition, $user);

        if ($request->input('referer') === 'decision') {
            return $this->redirector->route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $relatedDecision->department->slug,
                'decision' => $relatedDecision,
            ]);
        }

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $petition->department->slug,
            'petition' => $petition,
        ]);
    }
}
