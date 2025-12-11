<?php

declare(strict_types=1);

namespace App\Services\Authorisation;

use App\Enums\Authorization\Permission;
use App\Facades\ActiveDepartment;
use App\Models\Department;
use App\Models\User;
use App\Services\Authentication\AuthenticationException;
use App\Services\Authentication\AuthenticationServiceInterface;
use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Webmozart\Assert\Assert;

use function array_merge;
use function array_unique;
use function in_array;

class UserPermissionService
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authenticationService,
        private readonly DatabaseManager $databaseManager,
        private readonly ConfigRepository $configRepository,
        private readonly CacheManager $cacheManager,
    ) {
    }

    public function hasPermission(Permission $permission, User $user, ?Department $department = null): bool
    {
        return $this->cacheManager->store('array')->remember(
            'hasPermission.' . $permission->value . $user->id->toString() . ($department?->id ? '.' . $department->id : ''),
            null,
            function () use ($permission, $user, $department): bool {
                return in_array($permission->value, $this->permissions($user, $department), true);
            },
        );
    }

    /**
     * @throws AuthenticationException
     *
     * @deprecated Has to be moved elsewhere, as it is not a user permission service method.
     */
    public function hasPermissionAsCurrentUserAndActiveDepartment(Permission $permission): bool
    {
        $user = $this->authenticationService->user();

        return $this->hasPermission($permission, $user, ActiveDepartment::getActiveDepartment());
    }

    /**
     * @return array<string>
     */
    private function roles(User $user, ?Department $department = null): array
    {
        return $this->cacheManager->store('array')->remember(
            'roles.' . $user->id->toString() . ($department?->id ? '.' . $department->id : ''),
            null,
            function () use ($user, $department): iterable {
                $roles = $this->databaseManager->query()->select('role')
                    ->from('department_user')
                    ->where('user_id', $user->id)
                    ->where('department_id', $department?->id)
                    ->union(
                        $this->databaseManager->query()->select('role')
                            ->from('user_global_roles')
                            ->where('user_id', $user->id),
                    )->pluck('role')->all();
                Assert::allString($roles);

                return $roles;
            },
        );
    }

    /**
     * @return array<string>
     */
    private function permissions(User $user, ?Department $department = null): array
    {
        return $this->cacheManager->store('array')->remember(
            'permissions.' . $user->id->toString() . ($department?->id ? '.' . $department->id : ''),
            null,
            function () use ($user, $department): array {
                $roles = $this->roles($user, $department);
                $permissions = [];

                foreach ($roles as $role) {
                    $rolePermissions = $this->configRepository->array('permissions.roles_and_permissions.' . $role, []);
                    $permissions = array_merge($permissions, $rolePermissions);
                }

                Assert::allString($permissions);

                return array_unique($permissions);
            },
        );
    }
}
