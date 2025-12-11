<?php

declare(strict_types=1);

use App\Enums\Authorization\DepartmentRole;
use App\Enums\Authorization\GlobalRole;

return [
    'model_singular' => 'Rol',
    'model_plural' => 'Rollen',

    'global' => 'Applicatie',
    'department' => 'Afdeling',

    'global_roles' => [
        GlobalRole::ADMINISTRATOR->value => 'Applicatiebeheerder',
    ],
    'department_roles' => [
        DepartmentRole::READ->value => 'Lezen',
        DepartmentRole::WRITE->value => 'Schrijven',
    ],
];
