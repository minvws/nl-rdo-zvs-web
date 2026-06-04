<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\PetitionCustomDatesUpdateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Factories\View\Petition\PetitionCustomDatesViewFactory;
use App\Http\Requests\Petition\PetitionCustomDatesUpdateRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\Services\Authentication\AuthenticationException;
use App\View\HtmxHelper;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Illuminate\View\Factory;
use Throwable;

use function __;

final readonly class PetitionCustomDatesController
{
    public function __construct(
        private HtmxHelper $htmxHelper,
        private Factory $view,
        private PetitionCustomDatesViewFactory $petitionCustomDatesViewFactory,
        private Redirector $redirector,
    ) {
    }

    /**
     * @throws Throwable
     */
    #[Authorize(Ability::UPDATE, 'petition')]
    public function edit(Request $request, Department $department, Petition $petition): Response
    {
        return $this->htmxHelper->makeFormViewResponse($request, 'petition.custom-dates.edit', [
            'custom_dates' => $this->petitionCustomDatesViewFactory->build($petition),
            'petition' => $petition,
            'department' => $department,
        ]);
    }

    /**
     * @throws AuthenticationException
     * @throws Throwable
     */
    public function update(
        Request $request,
        Department $department,
        Petition $petition,
        PetitionCustomDatesUpdateRequest $customDatesUpdateRequest,
        PetitionCustomDatesUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View {
        $customDates = $customDatesUpdateRequest->getCustomDatesCollection();
        $action->execute($petition, $customDates, $user, $customDatesUpdateRequest->validated());

        if ($this->htmxHelper->isHtmxRequest($request)) {
            return $this->show($department, $petition);
        }

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])
            ->with('message.success', __('general.saved'));
    }

    /**
     * @throws Throwable
     */
    #[Authorize(Ability::VIEW, 'petition')]
    public function show(Department $department, Petition $petition): View
    {
        return $this->view->make('petition.custom-dates.show', [
            'custom_dates' => $this->petitionCustomDatesViewFactory->build($petition),
            'petition' => $petition,
            'department' => $department,
        ]);
    }
}
