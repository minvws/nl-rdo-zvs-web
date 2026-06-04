<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\CustomCost\CustomCostUpdateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\CustomCost\CustomCostUpdateRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\Services\Petition\PetitionException;
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
use TypeError;
use ValueError;

use function __;
use function array_values;

final readonly class PetitionCustomCostsController
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

        $availableCostTypes = $this->repository->get('custom_cost.' . $petitionVariant->value, []);
        $availableCostTypes = array_values($availableCostTypes);

        return $this->htmxHelper->makeFormViewResponse($request, 'petition.custom-costs.edit', [
            'petition' => $petition,
            'availableCostTypes' => $availableCostTypes,
            'department' => $department,
        ]);
    }

    /**
     * @throws PetitionException
     * @throws TypeError
     * @throws ValueError
     */
    #[Authorize(Ability::UPDATE, 'petition')]
    public function update(
        Request $request,
        Department $department,
        Petition $petition,
        CustomCostUpdateRequest $customCostsUpdateRequest,
        CustomCostUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View {
        $action->execute($petition, $user, $customCostsUpdateRequest->validated());

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
        return $this->view->make('petition.custom-costs.show', [
            'petition' => $petition,
            'department' => $department,
        ]);
    }
}
