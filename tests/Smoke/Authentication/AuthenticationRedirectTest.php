<?php

declare(strict_types=1);

namespace Tests\Smoke\Authentication;

use App\Models\Department;
use Tests\Smoke\SmokeTestCase;

use function __;
use function sprintf;

class AuthenticationRedirectTest extends SmokeTestCase
{
    public function testLoginWithDepartmentPermission(): void
    {
        $department = Department::factory()->create();

        $this->visit(sprintf('/%s/petitions', $department->slug))
            ->followRedirects()
            ->assertResponseOk()
            ->see(__('authentication.login'));
    }
}
