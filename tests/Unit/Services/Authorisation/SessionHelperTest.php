<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Authorisation;

use App\Exception\AppException;
use App\Services\Authorisation\SessionHelper;
use Illuminate\Contracts\Session\Session;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;
use Webmozart\Assert\InvalidArgumentException;

class SessionHelperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private MockInterface $session;
    private SessionHelper $sessionHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = Mockery::mock(Session::class);
        $this->sessionHelper = new SessionHelper($this->session);
    }

    public function testStoreDepartment(): void
    {
        $departmentSlug = $this->faker->slug;

        $this->session->shouldReceive('put')
            ->once()
            ->with('active_department', $departmentSlug);

        $this->sessionHelper->storeDepartmentSlug($departmentSlug);
    }

    public function testGetDepartmentSlugSuccess(): void
    {
        $departmentSlug = $this->faker->slug;

        $this->session->shouldReceive('get')
            ->once()
            ->with('active_department')
            ->andReturn($departmentSlug);

        $result = $this->sessionHelper->getDepartmentSlug();

        $this->assertEquals($departmentSlug, $result);
    }

    public function testGetDepartmentSlugThrowsExceptionWhenSessionKeyNotFound(): void
    {
        $this->session->shouldReceive('get')
            ->once()
            ->with('active_department')
            ->andReturnNull();

        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Unable to find the selected department slug (identified by "active_department") in the session');

        $this->sessionHelper->getDepartmentSlug();
    }

    public function testGetDepartmentSlugThrowsExceptionWhenSessionValueEmpty(): void
    {
        $this->session->shouldReceive('get')
            ->once()
            ->with('active_department')
            ->andReturn('');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The department slug (stored in session) must be a non-empty string.');

        $this->sessionHelper->getDepartmentSlug();
    }
}
