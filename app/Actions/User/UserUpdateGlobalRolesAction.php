<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\Authorization\GlobalRole;
use App\Events\GlobalRolesAssignedEvent;
use App\Events\GlobalRolesWithdrawnEvent;
use App\Models\User;
use App\Models\UserGlobalRole;
use Webmozart\Assert\Assert;

use function array_diff;
use function event;

class UserUpdateGlobalRolesAction
{
    /**
     * @param list<string> $globalRoles
     */
    public function execute(User $user, array $globalRoles): void
    {
        $existingRoles = UserGlobalRole::query()
            ->where('user_id', $user->id)
            ->pluck('role')
            ->map(static function (mixed $role) {
                Assert::isInstanceOf($role, GlobalRole::class);

                return $role->value;
            })
            ->all();

        $rolesToAdd = array_diff($globalRoles, $existingRoles);
        $rolesToDelete = array_diff($existingRoles, $globalRoles);

        if ($rolesToDelete !== []) {
            UserGlobalRole::query()
                ->where('user_id', $user->id)
                ->whereIn('role', $rolesToDelete)
                ->delete();
            event(new GlobalRolesWithdrawnEvent($user, $rolesToDelete));
        }

        foreach ($rolesToAdd as $role) {
            UserGlobalRole::query()->create([

                'user_id' => $user->id,
                'role' => $role,
            ]);
        }

        if ($rolesToAdd !== []) {
            event(new GlobalRolesAssignedEvent($user, $rolesToAdd));
        }
    }
}
