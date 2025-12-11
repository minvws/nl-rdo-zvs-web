<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Authentication;

use App\Actions\Authentication\LoginAttemptAction;
use App\Enums\Authorization\DepartmentRole;
use App\Enums\Authorization\GlobalRole;
use App\Enums\RouteName;
use App\Facades\Authentication;
use App\Http\Controllers\Authentication\AuthenticationController;
use App\Http\Requests\Authentication\LoginStoreRequest;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\User;
use App\Services\Authentication\AuthenticationException;
use App\Services\Authentication\AuthenticationServiceInterface;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

class AuthenticationControllerTest extends FeatureTestCase
{
    public function testLoginScreenCanBeRendered(): void
    {
        $this->getByRoute(RouteName::LOGIN)
            ->assertStatus(200);
    }

    public function testLoginRedirectsAuthenticatedUserToDashboard(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user)
            ->getByRoute(RouteName::LOGIN)
            ->assertRedirectToRoute('dashboard');
    }

    public function testUsersCanAuthenticateUsingTheLoginScreen(): void
    {
        Department::factory()->create();
        $password = $this->faker->password();

        $user = User::factory()
            ->fullyVerified()
            ->state(['password' => Hash::make($password)])
            ->create();

        $this->postByRoute(RouteName::LOGIN_ATTEMPT, data: [
            'email' => $user->email,
            'password' => $password,
        ])
            ->assertRedirectToRoute('profile.edit');

        $this->assertTrue(Authentication::user()->id->equals($user->id));
    }

    public function testUsersCanAuthenticateUsingTheLoginScreenWithRemember(): void
    {
        Department::factory()->create();
        $password = $this->faker->password();

        $user = User::factory()
            ->fullyVerified()
            ->state([
                'password' => Hash::make($password),
                'remember_token' => null,
            ])
            ->create();

        $this->postByRoute(RouteName::LOGIN_ATTEMPT, data: [
            'email' => $user->email,
            'password' => $password,
            'remember' => 1,
        ])
            ->assertRedirectToRoute('profile.edit');
        $this->assertTrue(Authentication::user()->id->equals($user->id));
    }

    public function testUsersCanNotAuthenticateWithInvalidPassword(): void
    {
        $user = User::factory()->fullyVerified()->create();

        $this->postByRoute(RouteName::LOGIN_ATTEMPT, data: [
            'email' => $user->email,
            'password' => $this->faker->unique()->password(),
        ]);

        $this->assertGuest();
    }

    public function testUsersCanNotAuthenticateWhenPasswordNotSet(): void
    {
        $user = User::factory()
            ->fullyVerified()
            ->unverifiedEmail()
            ->state(['password' => null])
            ->create();

        $this->postByRoute(RouteName::LOGIN_ATTEMPT, data: [
            'email' => $user->email,
            'password' => $this->faker->password(),
        ])->assertRedirectToRoute('login');

        $this->assertGuest();
    }

    public function testRateLimiterWhenTooManyAttempts(): void
    {
        ConfigHelper::set('app.rate_limit.max_attempts', 0);

        $user = User::factory()->fullyVerified()->create();

        $this->postByRoute(RouteName::LOGIN_ATTEMPT, data: [
            'email' => $user->email,
            'password' => $this->faker->unique()->password(),
        ]);
        $this->postByRoute(RouteName::LOGIN_ATTEMPT, data: [
            'email' => $user->email,
            'password' => $this->faker->unique()->password(),
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function testUsersCanAuthenticateAndHaveAnActiveDepartment(): void
    {
        $password = $this->faker->password();
        $department = Department::factory()->create();
        $user = User::factory()
            ->fullyVerified()
            ->withHashedPassword(Hash::make($password))
            ->create();
        DepartmentUser::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'role' => DepartmentRole::READ,
        ]);

        $this->postByRoute(RouteName::LOGIN_ATTEMPT, data: [
            'email' => $user->email,
            'password' => $password,
        ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, $department->slug);

        $this->assertTrue(Authentication::user()->id->equals($user->id));
    }

    public function testAdministratorWithoutDepartmentGoesToAdminPanel(): void
    {
        $password = $this->faker->password();
        $user = User::factory()
            ->fullyVerified()
            ->withHashedPassword(Hash::make($password))
            ->withGlobalRole(GlobalRole::ADMINISTRATOR)
            ->create();

        $this->postByRoute(RouteName::LOGIN_ATTEMPT, data: [
            'email' => $user->email,
            'password' => $password,
        ])
            ->assertRedirectToRoute(RouteName::ADMIN_SHOW);
    }

    public function testUsersCanLogout(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user)
            ->postByRoute('logout')
            ->assertRedirectToRoute('login');

        $this->assertGuest();
    }

    #[Test]
    public function userRetrievalFailsWhenNotAuthenticated(): void
    {
        $authService = $this->mock(AuthenticationServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('loginAttempt')->andReturn(true);
            $mock->shouldReceive('user')->andThrow(AuthenticationException::class);
        });

        $loginAction = $this->app->make(LoginAttemptAction::class);

        $controller = $this->app->make(AuthenticationController::class, [
            'authenticationService' => $authService,
        ]);

        $this->expectException(HttpException::class);

        $controller->loginAttempt(new LoginStoreRequest([
            'email' => 'test@example.com',
            'password' => 'password',
            'remember' => false,
        ]), $loginAction);
    }

    public function testUsersCanNotAuthenticateWhenInactive(): void
    {
        Department::factory()->create();
        $password = $this->faker->password();

        $user = User::factory()
            ->fullyVerified()
            ->state([
                'password' => Hash::make($password),
                'active' => false,
            ])
            ->create();

        $this->postByRoute(RouteName::LOGIN_ATTEMPT, data: [
            'email' => $user->email,
            'password' => $password,
        ]);

        $this->assertGuest();
    }
}
