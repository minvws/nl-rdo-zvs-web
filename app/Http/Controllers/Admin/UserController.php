<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\User\UserCreateAction;
use App\Actions\User\UserOtpResetAction;
use App\Actions\User\UserUpdateAction;
use App\Enums\Authorization\DepartmentRole;
use App\Enums\Authorization\GlobalRole;
use App\Enums\RouteName;
use App\Http\Requests\User\UserCreateRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\User\UserRoleService;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Throwable;

use function __;

final readonly class UserController
{
    public function __construct(
        private Redirector $redirector,
        private UserRoleService $userRoleService,
        private Factory $view,
        #[Config('app.pagination.user_items_per_page')]
        private int $paginationItemsPerPage,
    ) {
    }

    public function create(): View
    {
        return $this->view->make('user.create', [
            'departments' => Department::all(),
            'departmentRoles' => DepartmentRole::cases(),
            'globalRoles' => GlobalRole::cases(),
        ]);
    }

    public function edit(User $user): View
    {
        return $this->view->make('user.edit', [
            'departments' => Department::all(),
            'departmentRoles' => DepartmentRole::cases(),
            'globalRoles' => GlobalRole::cases(),
            'user' => $user,
            'userGlobalRoles' => $user->globalRoles()->pluck('role')->toArray(),
            'userDepartmentRoles' => $this->userRoleService->getDepartmentRoles($user->id),
        ]);
    }

    public function index(): View
    {
        $users = User::query()->paginate($this->paginationItemsPerPage);

        return $this->view->make('user.index', ['users' => $users]);
    }

    public function otpReset(
        User $user,
        UserOtpResetAction $action,
    ): RedirectResponse {
        $action->execute($user);

        return $this->redirector->route(RouteName::ADMIN_USER_EDIT, ['user' => $user])
            ->with('message.success', __('user.otp_reset_success'));
    }

    public function store(
        UserCreateRequest $userCreateRequest,
        UserCreateAction $action,
    ): RedirectResponse {
        $user = $action->execute($userCreateRequest->validated());

        return $this->redirector->route(RouteName::ADMIN_USER_EDIT, ['user' => $user])
            ->with('message.success', __('general.saved'));
    }

    /**
     * @throws Throwable
     */
    public function update(
        User $user,
        UserUpdateRequest $userUpdateRequest,
        UserUpdateAction $action,
    ): RedirectResponse {
        $action->execute($user, $userUpdateRequest->validated());

        return $this->redirector->route(RouteName::ADMIN_USER_EDIT, ['user' => $user])
            ->with('message.success', __('general.saved'));
    }
}
