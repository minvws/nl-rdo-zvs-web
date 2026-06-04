<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Authorization\Permission;
use App\Services\Authentication\AuthenticationException;
use App\Services\Authentication\AuthenticationServiceInterface;
use App\Services\Authorisation\ActiveDepartmentService;
use App\View\HtmxHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final readonly class DepartmentPanelController
{
    public function __construct(
        private ActiveDepartmentService $activeDepartmentService,
        private AuthenticationServiceInterface $authenticationService,
        private HtmxHelper $htmxHelper,
    ) {
    }

    /**
     * @throws AuthenticationException
     */
    #[Authorize(Permission::DEPARTMENT_READ)]
    public function show(Request $request): Response
    {
        $activeDepartment = $this->activeDepartmentService->getActiveDepartment();

        $user = $this->authenticationService->user();

        $departments = $user->departments()->get()->unique('id');

        return $this->htmxHelper->makeFormViewResponse($request, 'departments.show', [
            'departments' => $departments,
            'activeDepartment' => $activeDepartment,
        ]);
    }
}
