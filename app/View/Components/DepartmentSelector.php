<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Services\Authentication\AuthenticationException;
use App\Services\Authentication\AuthenticationServiceInterface;
use App\Services\Authorisation\ActiveDepartmentService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\View\Factory;
use Webmozart\Assert\Assert;

class DepartmentSelector extends Component
{
    public function __construct(
        private readonly ActiveDepartmentService $activeDepartmentService,
        private readonly Factory $view,
        private readonly AuthenticationServiceInterface $authenticationService,
    ) {
    }

    /**
     * @throws AuthenticationException
     */
    public function render(): View
    {
        $activeDepartment = $this->activeDepartmentService->getActiveDepartment();
        Assert::notNull($activeDepartment);

        $user = $this->authenticationService->user();

        $departments = $user->departments()->get()->unique('id');
        $hasMultipleDepartments = $departments->count() > 1;

        return $this->view->make('components.department-selector', [
            'hasMultipleDepartments' => $hasMultipleDepartments,
            'activeDepartment' => $activeDepartment,
        ]);
    }
}
