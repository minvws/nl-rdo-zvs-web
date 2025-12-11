<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Enums\Ability;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\Services\Petition\PetitionFilterService;
use App\Services\Petition\PetitionIndexService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class PetitionIndexController
{
    public function __construct(
        private PetitionFilterService $petitionFilterService,
        private PetitionIndexService $petitionIndexService,
        private Factory $view,
        private Gate $gate,
    ) {
    }

    public function __invoke(Request $request, Department $department, #[CurrentUser] User $user): View|RedirectResponse
    {
        $this->gate->authorize(Ability::VIEW_ANY, Petition::class);
        $redirectResponse = $this->petitionFilterService->handleFilterPersistence($request, $user, $department);
        if ($redirectResponse instanceof RedirectResponse) {
            return $redirectResponse;
        }
        $indexData = $this->petitionIndexService->getIndexData($request, $department, $user);

        return $this->view->make('petition.index', $indexData);
    }
}
