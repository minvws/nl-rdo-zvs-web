<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\PetitionStatusHistoryDeleteAction;
use App\Actions\Petition\PetitionStatusUpdateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\Petition\PetitionStatusUpdateRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;

use function __;

final readonly class PetitionStatusController
{
    public function __construct(
        private Redirector $redirector,
        private Factory $view,
        private UrlGenerator $urlGenerator,
    ) {
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function edit(Department $department, Petition $petition): View
    {
        $petitionStatuses = PetitionStatus::query()->where('petition_type_id', $petition->petitionType->id)
            ->orderBy('order')
            ->get();

        $statusHistory = $petition->petitionStatusHistories()
            ->with('petitionStatus')
            ->orderBy('date')->oldest()
            ->get();

        return $this->view->make('petition.change-status.edit', [
            'department' => $department,
            'petition' => $petition,
            'petitionStatuses' => $petitionStatuses,
            'statusHistory' => $statusHistory,
        ]);
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function update(
        Department $department,
        Petition $petition,
        PetitionStatusUpdateRequest $request,
        PetitionStatusUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        /** @var array{petition_status_id: string, petition_status_date: string, petition_status_comment: string|null} $attributes */
        $attributes = $request->validated();
        $action->execute($petition, $user, $attributes);

        return $this->redirector->to($this->urlGenerator->route(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ]))->with('message.success', __('general.saved'));
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function destroy(
        Department $department,
        Petition $petition,
        PetitionStatusHistory $petitionStatusHistory,
        #[CurrentUser]
        User $user,
        PetitionStatusHistoryDeleteAction $action,
    ): RedirectResponse {
        $action->execute($petition, $user, $petitionStatusHistory);

        return $this->redirector->to($this->urlGenerator->route(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ]))->with('message.success', __('general.deleted'));
    }
}
