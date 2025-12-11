<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Events\UserCreatedEvent;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Database\DatabaseManager;

use function event;

class UserCreateAction
{
    public function __construct(
        private readonly UserUpdateDepartmentRolesAction $userUpdateDepartmentRolesAction,
        private readonly UserUpdateGlobalRolesAction $userUpdateGlobalRolesAction,
        private readonly UserService $userService,
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(array $attributes): User
    {
        /** @var array<string, list<string>> $departmentRoles */
        $departmentRoles = $attributes['department_roles'] ?? [];
        /** @var list<string> $globalRoles */
        $globalRoles = $attributes['global_roles'] ?? [];

        unset($attributes['department_roles'], $attributes['global_roles']);

        $newUser = $this->databaseManager->transaction(function () use ($attributes, $departmentRoles, $globalRoles): User {
            $newUser = User::query()->create($attributes);
            $this->userUpdateGlobalRolesAction->execute($newUser, $globalRoles);
            $this->userUpdateDepartmentRolesAction->execute($newUser, $departmentRoles);

            return $newUser;
        });

        $this->userService->sendEmailVerificationMail($newUser);

        event(new UserCreatedEvent($newUser));

        return $newUser;
    }
}
