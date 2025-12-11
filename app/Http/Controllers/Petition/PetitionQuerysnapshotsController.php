<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\QuerysnapshotUpdateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\ExternalUrl\QuerysnapshotUpdateRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\View\HtmxHelper;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\View\Factory;

use function __;
use function array_values;

final readonly class PetitionQuerysnapshotsController
{
    public function __construct(
        private HtmxHelper $htmxHelper,
        private Factory $view,
        private Redirector $redirector,
        private Repository $repository,
        private Gate $gate,
    ) {
    }

    public function edit(Request $request, Department $department, Petition $petition): Response
    {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $petitionTypeType = $petition->petitionType->type;

        $availableQuerysnapshotTypes = $this->repository->array('querysnapshot.' . $petitionTypeType->value, []);
        $availableQuerysnapshotTypes = array_values($availableQuerysnapshotTypes);

        return $this->htmxHelper->makeFormViewResponse($request, 'petition.querysnapshots.edit', [
            'petition' => $petition,
            'availableQuerysnapshotTypes' => $availableQuerysnapshotTypes,
            'department' => $department,
        ]);
    }

    public function update(
        Request $request,
        Department $department,
        Petition $petition,
        QuerysnapshotUpdateRequest $querysnapshotUpdateRequest,
        QuerysnapshotUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $action->execute($petition, $user, $querysnapshotUpdateRequest->validated());

        $petition->refresh();

        if ($this->htmxHelper->isHtmxRequest($request)) {
            return $this->show($department, $petition);
        }

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])
            ->with('message.success', __('general.saved'));
    }

    public function show(Department $department, Petition $petition): View
    {
        $this->gate->authorize(Ability::VIEW, $petition);

        return $this->view->make('petition.querysnapshots.show', [
            'petition' => $petition,
        ]);
    }
}
