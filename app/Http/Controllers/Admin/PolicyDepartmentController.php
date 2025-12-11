<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\PolicyDepartment\CreatePolicyDepartmentAction;
use App\Actions\PolicyDepartment\UpdatePolicyDepartmentAction;
use App\Enums\RouteName;
use App\Http\Requests\PolicyDepartment\PolicyDepartmentStoreRequest;
use App\Http\Requests\PolicyDepartment\PolicyDepartmentUpdateRequest;
use App\Models\PolicyDepartment;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

use function __;

final readonly class PolicyDepartmentController
{
    public function __construct(
        private Redirector $redirector,
        private Factory $view,
        #[Config('app.pagination.items_per_page')]
        private int $paginationItemsPerPage = 20,
    ) {
    }

    public function index(): View
    {
        $policyDepartments = PolicyDepartment::query()->orderBy('name')->cursorPaginate($this->paginationItemsPerPage);

        return $this->view->make('policy-department.index', ['policyDepartments' => $policyDepartments]);
    }

    public function create(): View
    {
        return $this->view->make('policy-department.create');
    }

    public function store(PolicyDepartmentStoreRequest $policyDepartmentStore, CreatePolicyDepartmentAction $action): RedirectResponse
    {
        $action->execute($policyDepartmentStore->validated());

        return $this->redirector->route(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->with('message.success', __('general.saved'));
    }

    public function edit(PolicyDepartment $policyDepartment): View
    {
        return $this->view->make('policy-department.edit', [
            'policyDepartment' => $policyDepartment,
        ]);
    }

    public function update(PolicyDepartment $policyDepartment, PolicyDepartmentUpdateRequest $policyDepartmentUpdate, UpdatePolicyDepartmentAction $action): RedirectResponse
    {
        $action->execute($policyDepartment, $policyDepartmentUpdate->validated());

        return $this->redirector->route(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->with('message.success', __('general.saved'));
    }
}
