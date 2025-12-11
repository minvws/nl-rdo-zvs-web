<?php

declare(strict_types=1);

namespace Tests\Unit\Enum\Authorisation;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\Authorization\GlobalRole;
use Tests\TestCase;

use function array_map;
use function array_merge;
use function array_unique;
use function count;

class UniqueRoleTest extends TestCase
{
    public function testUniqueRoleNames(): void
    {
        $globalRoles = array_map(fn(GlobalRole $role) => $role->value, GlobalRole::cases());
        $departmentRoles = array_map(fn(DepartmentRole $role) => $role->value, DepartmentRole::cases());

        $roles = array_merge($globalRoles, $departmentRoles);

        $this->assertEquals(count($roles), count(array_unique($roles)), 'values in global roles & department roles should be unique');
    }
}
