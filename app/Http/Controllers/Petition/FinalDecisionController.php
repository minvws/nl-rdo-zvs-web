<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\SetFinalDecisionAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\Petition\SetFinalDecisionRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;

final readonly class FinalDecisionController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
    ) {
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function edit(Department $department, Petition $petition): View
    {
        return $this->view->make('petition.final-decision', [
            'department' => $department,
            'petition' => $petition,
        ]);
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function update(
        Department $department,
        Petition $petition,
        SetFinalDecisionRequest $request,
        SetFinalDecisionAction $action,
        #[CurrentUser] User $user,
    ): RedirectResponse {
        /** @var array{final_decision_id?: string|null} $attributes */
        $attributes = $request->validated();
        $action->execute($petition, $attributes, $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]);
    }
}
