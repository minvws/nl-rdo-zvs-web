<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\PetitionCustomProperties\PetitionCustomPetitionPropertiesUpdateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\Petition\PetitionCustomPetitionPropertiesUpdateRequest;
use App\Models\CustomPetitionProperty;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\View\Components\Petition\CustomProperties\Show;
use App\View\HtmxHelper;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;

use function __;

final readonly class PetitionCustomPetitionPropertiesController
{
    public function __construct(
        private Redirector $redirector,
        private HtmxHelper $htmxHelper,
        private Show $showComponent,
    ) {
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function edit(
        Request $request,
        Department $department,
        Petition $petition,
    ): Response {
        $customPetitionProperties = CustomPetitionProperty::query()->where('petition_type_id', $petition->petition_type_id)
            ->orderBy('ordering')
            ->get();

        $petitionCustomPetitionProperties = CustomPetitionProperty::query()->whereRelation('petitions', 'id', $petition->id)
            ->get();

        $petitionCustomPetitionPropertyIds = $petitionCustomPetitionProperties
            ->map(static function (object $customPetitionProperty): string {
                return $customPetitionProperty->id->toString();
            })
            ->all();

        return $this->htmxHelper->makeFormViewResponse($request, 'petition.custom_petition_property.edit', [
            'petition' => $petition,
            'custom_petition_properties' => $customPetitionProperties,
            'petition_custom_petition_property_ids' => $petitionCustomPetitionPropertyIds,
            'department' => $department,
        ]);
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function update(
        Request $request,
        Department $department,
        Petition $petition,
        PetitionCustomPetitionPropertiesUpdateRequest $petitionCustomPetitionPropertiesUpdateRequest,
        PetitionCustomPetitionPropertiesUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View {
        $action->execute($petition, $user, $petitionCustomPetitionPropertiesUpdateRequest->validated());

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
        $this->showComponent->petition = $petition;
        $this->showComponent->department = $department;

        return $this->showComponent->render();
    }
}
