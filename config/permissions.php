<?php

declare(strict_types=1);

use App\Enums\Authorization\DepartmentRole;
use App\Enums\Authorization\GlobalRole;
use App\Enums\Authorization\Permission;

return [
    'roles_and_permissions' => [
        GlobalRole::ADMINISTRATOR->value => [
            Permission::CONTACT_MANAGE->value,
            Permission::DEPARTMENT_READ->value,
            Permission::USER_WRITE->value,
            Permission::USER_READ->value,
            Permission::PUBLIC_HOLIDAY_WRITE->value,
            Permission::PUBLIC_HOLIDAY_READ->value,
            Permission::PETITION_TYPE_WRITE->value,
            Permission::PETITION_TYPE_READ->value,
            Permission::PETITION_CATEGORY_WRITE->value,
            Permission::PETITION_CATEGORY_READ->value,
            Permission::TEAM_WRITE->value,
            Permission::TEAM_READ->value,
            Permission::POLICY_DEPARTMENT_WRITE->value,
            Permission::POLICY_DEPARTMENT_READ->value,
            Permission::ADMIN_PANEL_VIEW->value,
            Permission::PETITION_NUMBER_OVERRULE->value,
        ],
        DepartmentRole::WRITE->value => [
            Permission::DEPARTMENT_READ->value,
            Permission::USER_READ->value,
            Permission::PUBLIC_HOLIDAY_READ->value,
            Permission::PETITION_TYPE_READ->value,
            Permission::PETITION_CATEGORY_READ->value,
            Permission::TEAM_READ->value,

            Permission::CONTACT_WRITE->value,
            Permission::CONTACT_READ->value,
            Permission::PETITION_WRITE->value,
            Permission::PETITION_READ->value,
            Permission::DECISION_WRITE->value,
            Permission::DECISION_READ->value,
        ],
        DepartmentRole::READ->value => [
            Permission::DEPARTMENT_READ->value,
            Permission::USER_READ->value,
            Permission::PUBLIC_HOLIDAY_READ->value,
            Permission::PETITION_TYPE_READ->value,
            Permission::PETITION_CATEGORY_READ->value,
            Permission::TEAM_READ->value,

            Permission::CONTACT_READ->value,
            Permission::PETITION_READ->value,
            Permission::DECISION_READ->value,
        ],
    ],
];
