<?php

declare(strict_types=1);

namespace App\Http\Controllers\Decision;

use App\Actions\Decision\DecisionUnarchiveAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Models\Decision;
use App\Models\Department;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Throwable;

final readonly class DecisionUnarchiveController
{
    public function __construct(
        private Redirector $redirector,
    ) {
    }

    /**
     * @throws Throwable
     */
    #[Authorize(Ability::UNARCHIVE, 'decision')]
    public function __invoke(
        Department $department,
        Decision $decision,
        DecisionUnarchiveAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $action->execute($decision, $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
            'department' => $department,
            'decision' => $decision,
        ]);
    }
}
