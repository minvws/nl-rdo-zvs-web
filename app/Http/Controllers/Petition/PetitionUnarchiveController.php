<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\PetitionUnarchiveAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Throwable;

final readonly class PetitionUnarchiveController
{
    public function __construct(
        private Redirector $redirector,
    ) {
    }

    /**
     * @throws Throwable
     */
    #[Authorize(Ability::UNARCHIVE, 'petition')]
    public function __invoke(
        Department $department,
        Petition $petition,
        PetitionUnarchiveAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $action->execute($petition, $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]);
    }
}
