<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\ExternalUrl\ExternalUrlUpdateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\ExternalUrl\ExternalUrlUpdateRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\View\HtmxHelper;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Illuminate\View\Factory;

use function __;
use function array_values;

final readonly class PetitionExternalUrlsController
{
    public function __construct(
        private HtmxHelper $htmxHelper,
        private Factory $view,
        private Redirector $redirector,
        private Repository $repository,
    ) {
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function edit(Request $request, Department $department, Petition $petition): Response
    {
        $petitionVariant = $petition->petitionType->type;

        $availableUrlTypes = $this->repository->array('external_url.' . $petitionVariant->value, []);
        $availableUrlTypes = array_values($availableUrlTypes);

        return $this->htmxHelper->makeFormViewResponse($request, 'petition.external-urls.edit', [
            'department' => $department,
            'petition' => $petition,
            'availableUrlTypes' => $availableUrlTypes,
        ]);
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function update(
        Request $request,
        Department $department,
        Petition $petition,
        ExternalUrlUpdateRequest $externalUrlsUpdateRequest,
        ExternalUrlUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View {
        $action->execute($petition, $user, $externalUrlsUpdateRequest->validated());

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

    #[Authorize(Ability::VIEW, 'petition')]
    public function show(Department $department, Petition $petition): View
    {
        return $this->view->make('petition.external-urls.show', [
            'department' => $department,
            'petition' => $petition,
        ]);
    }
}
