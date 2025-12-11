<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Events\UserUpdatedEvent;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Throwable;

use function event;

class UserUpdateAction
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
        private readonly UserUpdateDepartmentRolesAction $userUpdateDepartmentRolesAction,
        private readonly UserUpdateGlobalRolesAction $userUpdateGlobalRolesAction,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    public function execute(User $user, array $attributes): void
    {
        /** @var array<string, list<string>> $departmentRoles */
        $departmentRoles = $attributes['department_roles'] ?? [];
        /** @var list<string> $globalRoles */
        $globalRoles = $attributes['global_roles'] ?? [];

        unset($attributes['department_roles'], $attributes['global_roles']);

        $this->databaseManager->transaction(function () use ($user, $attributes, $globalRoles, $departmentRoles): void {
            $user->update($attributes);
            $this->userUpdateGlobalRolesAction->execute($user, $globalRoles);
            $this->userUpdateDepartmentRolesAction->execute($user, $departmentRoles);
        });

        if ($user->wasChanged()) {
            event(new UserUpdatedEvent($user));
        }
    }
}
