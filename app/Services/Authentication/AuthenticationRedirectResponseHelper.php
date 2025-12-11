<?php

declare(strict_types=1);

namespace App\Services\Authentication;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Services\Authorisation\UserPermissionService;

readonly class AuthenticationRedirectResponseHelper
{
    public function __construct(
        private UserPermissionService $userPermissionService,
    ) {
    }

    /**
     * @param object{slug: string}|null $activeDepartment
     *
     * @return object{route: string|RouteName, parameters: array<string, string>}
     *
     * @throws AuthenticationException
     */
    public function determineDestinationAfterAuthentication(?object $activeDepartment): object
    {
        if ($activeDepartment !== null) {
            return (object) [
                'route' => RouteName::DEPARTMENTS_PETITIONS_INDEX,
                'parameters' => [
                    'department' => $activeDepartment->slug,
                ],
            ];
        }

        if ($this->currentUserHasAdminPanelViewPermission()) {
            return (object) [
                'route' => RouteName::ADMIN_SHOW,
                'parameters' => [],
            ];
        }

        return (object) [
            'route' => 'profile.edit',
            'parameters' => [],
        ];
    }

    /**
     * @throws AuthenticationException
     */
    private function currentUserHasAdminPanelViewPermission(): bool
    {
        return $this->userPermissionService->hasPermissionAsCurrentUserAndActiveDepartment(Permission::ADMIN_PANEL_VIEW);
    }
}
