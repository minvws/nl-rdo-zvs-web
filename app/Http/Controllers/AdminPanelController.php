<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\Authorisation\ActiveDepartmentService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

final readonly class AdminPanelController
{
    public function __construct(
        private ActiveDepartmentService $activeDepartmentService,
        private Factory $view,
    ) {
    }

    public function show(): View
    {
        $activeDepartment = $this->activeDepartmentService->getActiveDepartment();

        return $this->view->make('admin.view', [
            'departments' => Department::all(),
            'activeDepartment' => $activeDepartment,
        ]);
    }
}
