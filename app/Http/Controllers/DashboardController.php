<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RouteName;
use App\Facades\ActiveDepartment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

use function abort;

final readonly class DashboardController
{
    public function __construct(
        private Redirector $redirector,
    ) {
    }

    public function __invoke(): RedirectResponse
    {
        $activeDepartment = ActiveDepartment::getActiveDepartment();
        if ($activeDepartment === null) {
            abort(403);
        }

        // A dashboard is part of the roadmap (i.e. "Overzichten"), so we want a 302 response for now
        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_INDEX, $activeDepartment->slug);
    }
}
