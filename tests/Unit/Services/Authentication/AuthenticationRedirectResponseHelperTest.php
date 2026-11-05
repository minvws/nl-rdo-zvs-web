<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Authentication;

use App\Enums\RouteName;
use App\Models\User;
use App\Services\Authentication\AuthenticationRedirectResponseHelper;
use App\Services\Authentication\AuthenticationServiceInterface;
use App\Services\Authorisation\UserPermissionService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthenticationRedirectResponseHelperTest extends TestCase
{
    private AuthenticationRedirectResponseHelper $helper;
    private MockInterface&UserPermissionService $userPermissionService;
    private MockInterface&AuthenticationServiceInterface $authenticationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userPermissionService = Mockery::mock(UserPermissionService::class);
        $this->authenticationService = Mockery::mock(AuthenticationServiceInterface::class);
        $this->helper = new AuthenticationRedirectResponseHelper($this->authenticationService, $this->userPermissionService);
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

        $this->authenticationService
            ->shouldReceive('user')
            ->once()
            ->andReturn(new User());
        $this->userPermissionService
            ->shouldReceive('hasPermission')
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

        $this->authenticationService
            ->shouldReceive('user')
            ->once()
            ->andReturn(new User());
        $this->userPermissionService
            ->shouldReceive('hasPermission')
            ->once()
            ->andReturn(false);

        $response = $this->helper->determineDestinationAfterAuthentication(null);

        $this->assertEquals($expectedResponse, $response);
    }
}
