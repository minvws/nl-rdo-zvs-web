<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\PetitionCategory\CreatePetitionCategory;
use App\Actions\PetitionCategory\UpdatePetitionCategory;
use App\Enums\RouteName;
use App\Http\Requests\PetitionCategory\PetitionCategoryCreateRequest;
use App\Http\Requests\PetitionCategory\PetitionCategoryUpdateRequest;
use App\Models\Department;
use App\Models\PetitionCategory;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

use function __;

final readonly class PetitionCategoryController
{
    public function __construct(
        private Redirector $redirector,
        private Factory $view,
        #[Config('app.pagination.items_per_page')]
        private int $paginationItemsPerPage,
    ) {
    }

    public function index(Department $department): View
    {
        $petitionCategories = PetitionCategory::query()
            ->where('department_id', $department->id)
            ->paginate($this->paginationItemsPerPage);

        return $this->view->make('petition-categories.index', [
            'petitionCategories' => $petitionCategories,
            'department' => $department,
        ]);
    }

    public function create(Department $department): View
    {
        return $this->view->make('petition-categories.create', [
            'department' => $department,
        ]);
    }

    public function store(Department $department, PetitionCategoryCreateRequest $petitionTypeCreateRequest, CreatePetitionCategory $createPetitionCategory): RedirectResponse
    {
        $createPetitionCategory->execute($department, $petitionTypeCreateRequest->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_INDEX, ['department' => $department])
            ->with('message.success', __('general.saved'));
    }

    public function edit(Department $department, PetitionCategory $petitionCategory): View
    {
        return $this->view->make('petition-categories.edit', [
            'petitionCategory' => $petitionCategory,
            'department' => $department,
        ]);
    }

    public function update(Department $department, PetitionCategory $petitionCategory, PetitionCategoryUpdateRequest $petitionCategoryUpdateRequest, UpdatePetitionCategory $updatePetitionCategory): RedirectResponse
    {
        $updatePetitionCategory->execute($petitionCategory, $petitionCategoryUpdateRequest->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_INDEX, ['department' => $department])
            ->with('message.success', __('general.saved'));
    }
}
