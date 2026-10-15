<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\PetitionTagsUpdateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\Petition\PetitionTagsUpdateRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\Tag;
use App\Models\User;
use App\View\HtmxHelper;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Throwable;

use function __;

final readonly class PetitionTagController
{
    public function __construct(
        private HtmxHelper $htmxHelper,
        private Redirector $redirector,
        private Factory $view,
    ) {
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function edit(Request $request, Department $department, Petition $petition): Response
    {
        return $this->htmxHelper->makeFormViewResponse($request, 'petition.tags.edit', [
            'petition' => $petition,
            'tags' => Tag::query()->selectableForPetition($petition)->get(),
            'department' => $department,
        ]);
    }

    #[Authorize(Ability::VIEW, 'petition')]
    public function show(Department $department, Petition $petition): View
    {
        return $this->view->make('petition.tags.show', ['petition' => $petition]);
    }

    /**
     * @throws Throwable
     */
    #[Authorize(Ability::UPDATE, 'petition')]
    public function update(
        Request $request,
        Department $department,
        Petition $petition,
        PetitionTagsUpdateRequest $petitionTagsUpdateRequest,
        PetitionTagsUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View {
        $action->execute($petition, $user, $petitionTagsUpdateRequest->validated());

        if ($this->htmxHelper->isHtmxRequest($request)) {
            return $this->view->make('petition.tags.show', ['petition' => $petition]);
        }

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])
            ->with('message.success', __('general.saved'));
    }
}
