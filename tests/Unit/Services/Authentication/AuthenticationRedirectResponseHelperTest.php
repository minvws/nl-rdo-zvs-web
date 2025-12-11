<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Authentication;

use App\Enums\RouteName;
use App\Services\Authentication\AuthenticationRedirectResponseHelper;
use App\Services\Authorisation\UserPermissionService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthenticationRedirectResponseHelperTest extends TestCase
{
    private AuthenticationRedirectResponseHelper $helper;
    private MockInterface&UserPermissionService $userPermissionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userPermissionService = Mockery::mock(UserPermissionService::class);
        $this->helper = new AuthenticationRedirectResponseHelper($this->userPermissionService);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testRedirectsToActiveDepartmentWhenProvided(): void
    {
        $departmentSlug = $this->faker()->word();
        $activeDepartment = (object) [
            'slug' => $departmentSlug,
        ];

        $expectedRoute = RouteName::DEPARTMENTS_PETITIONS_INDEX;
        $expectedResponse = (object) [
            'route' => $expectedRoute,
            'parameters' => ['department' => $departmentSlug],
        ];

        $response = $this->helper->determineDestinationAfterAuthentication($activeDepartment);

        $this->assertEquals($expectedResponse, $response);
    }

    public function testRedirectsToAdminPanelWhenUserHasPermission(): void
    {
        $expectedRoute = RouteName::ADMIN_SHOW;
        $expectedResponse = (object) [
            'route' => $expectedRoute,
            'parameters' => [],
        ];

        $this->userPermissionService
            ->shouldReceive('hasPermissionAsCurrentUserAndActiveDepartment')
            ->once()
            ->andReturn(true);

        $response = $this->helper->determineDestinationAfterAuthentication(null);

        $this->assertEquals($expectedResponse, $response);
    }

    public function testRedirectsToProfileEditWhenNoPermissionAndNoDepartment(): void
    {
        $expectedRoute = 'profile.edit';
        $expectedResponse = (object) [
            'route' => $expectedRoute,
            'parameters' => [],
        ];

        $this->userPermissionService
            ->shouldReceive('hasPermissionAsCurrentUserAndActiveDepartment')
            ->once()
            ->andReturn(false);

        $response = $this->helper->determineDestinationAfterAuthentication(null);

        $this->assertEquals($expectedResponse, $response);
    }
}
