<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\PetitionStatusUpdateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\Petition\PetitionStatusUpdateRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;

use function __;

final readonly class PetitionStatusController
{
    public function __construct(
        private Redirector $redirector,
        private Factory $view,
        private Gate $gate,
        private UrlGenerator $urlGenerator,
    ) {
    }

    public function edit(Department $department, Petition $petition): View
    {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $petitionStatuses = PetitionStatus::query()->where('petition_type_id', $petition->petitionType->id)
            ->orderBy('order')
            ->get();

        return $this->view->make('petition.change-status.edit', [
            'department' => $department,
            'petition' => $petition,
            'petitionStatuses' => $petitionStatuses,
        ]);
    }

    public function update(
        Department $department,
        Petition $petition,
        PetitionStatusUpdateRequest $request,
        PetitionStatusUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $action->execute($petition, $user, $request->validated());

        return $this->redirector->to($this->urlGenerator->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]))->with('message.success', __('general.saved'));
    }
}
