<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\PetitionUpdateAction;
use App\Config\Config;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\Petition\PetitionUpdateRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionStatus;
use App\Models\User;
use App\View\HtmxHelper;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Throwable;

use function __;
use function sprintf;

final readonly class PetitionPropertiesController
{
    public function __construct(
        private Redirector $redirector,
        private Factory $view,
        private HtmxHelper $htmxHelper,
        private Gate $gate,
    ) {
    }

    public function edit(Request $request, Department $department, Petition $petition): Response
    {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $petitionTypeConfiguration = Config::array(
            sprintf('petition_type_type.%s.optional_form_fields', $petition->petitionType->type->value),
        );

        return $this->htmxHelper->makeFormViewResponse($request, 'petition.properties.edit', [
            'petition' => $petition,
            'petitionCategories' => PetitionCategory::query()
                ->where('department_id', $department->id)
                ->active()->get(),
            'petitionStatuses' => PetitionStatus::query()->orderBy('order')->limit(100)->get(),
            'petitionTypeConfiguration' => $petitionTypeConfiguration,
            'department' => $department,
        ]);
    }

    public function show(Department $department, Petition $petition): View
    {
        $this->gate->authorize(Ability::VIEW, $petition);

        $petitionTypeConfiguration = Config::array(
            sprintf('petition_type_type.%s.optional_form_fields', $petition->petitionType->type->value),
        );

        return $this->view->make('petition.properties.show', [
            'petition' => $petition,
            'petitionTypeConfiguration' => $petitionTypeConfiguration,
            'department' => $department,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function update(
        Request $request,
        Department $department,
        Petition $petition,
        PetitionUpdateRequest $petitionUpdateRequest,
        PetitionUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $action->execute($petition, $user, $petitionUpdateRequest->validated());

        if ($this->htmxHelper->isHtmxRequest($request)) {
            return $this->show($department, $petition);
        }

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])
            ->with('message.success', __('general.saved'));
    }
}
