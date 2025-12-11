<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Filter\FilterSaveAction;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;

final readonly class PetitionFilterController
{
    public function __construct(
        private Redirector $redirector,
        private UrlGenerator $urlGenerator,
    ) {
    }

    public function __invoke(Request $request, Department $department, FilterSaveAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        $filters = $request->array('filter');

        $action->execute($user, $department, 'petition', $filters);

        return $this->redirector->to($this->urlGenerator->route(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
            'department' => $department,
            'filter' => $filters,
        ]));
    }
}
