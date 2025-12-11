<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\PetitionType\PetitionTypeCreateAction;
use App\Actions\PetitionType\PetitionTypeUpdateAction;
use App\Enums\RouteName;
use App\Http\Requests\PetitionType\PetitionTypeCreateRequest;
use App\Http\Requests\PetitionType\PetitionTypeUpdateRequest;
use App\Models\Department;
use App\Models\PetitionType;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;

use function __;
use function array_merge;

final readonly class PetitionTypeController
{
    public function __construct(
        private Redirector $redirector,
        private Factory $view,
        #[Config('app.pagination.items_per_page')]
        private int $paginationItemsPerPage,
        private UrlGenerator $urlGenerator,
    ) {
    }

    public function index(Department $department): View
    {
        $petitionTypes = PetitionType::query()
            ->where('department_id', $department->id)
            ->orderBy('id')->cursorPaginate($this->paginationItemsPerPage);

        return $this->view->make('petition-types.index', [
            'petitionTypes' => $petitionTypes,
            'department' => $department,
        ]);
    }

    public function create(Department $department): View
    {
        return $this->view->make('petition-types.create', [
            'department' => $department,
        ]);
    }

    public function store(
        Department $department,
        PetitionTypeCreateRequest $petitionTypeCreateRequest,
        PetitionTypeCreateAction $action,
    ): RedirectResponse {
        $data = array_merge($petitionTypeCreateRequest->validated(), [
            'department_id' => $department->id,
        ]);

        $action->execute($data);

        return $this->redirector->to(
            $this->urlGenerator->route(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_INDEX, ['department' => $department]),
        )
            ->with('message.success', __('general.saved'));
    }

    public function edit(
        Department $department,
        PetitionType $petitionType,
    ): View {
        return $this->view->make('petition-types.edit', [
            'petitionType' => $petitionType,
            'department' => $department,
        ]);
    }

    public function update(
        Department $department,
        PetitionType $petitionType,
        PetitionTypeUpdateRequest $petitionTypeUpdateRequest,
        PetitionTypeUpdateAction $action,
    ): RedirectResponse {
        $action->execute($petitionType, $petitionTypeUpdateRequest->validated());

        return $this->redirector->to(
            $this->urlGenerator->route(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_INDEX, ['department' => $department]),
        )
            ->with('message.success', __('general.saved'));
    }
}
