<?php

declare(strict_types=1);

namespace Tests\Smoke\Authentication;

use App\Enums\Authorization\DepartmentRole;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Smoke\SmokeTestCase;

use function __;
use function sprintf;

class LoginTest extends SmokeTestCase
{
    public function testLoginWithDepartmentPermission(): void
    {
        $code = sprintf('%06d', $this->faker->numberBetween(0, 999_999));

        $password = $this->faker->password();

        $department = Department::factory()->create();

        $user = User::factory()
            ->fullyVerified()
            ->withHashedPassword(Hash::make($password))
            ->create([
                'last_visited_department_id' => $department->id,
            ]);

//        UserGlobalRole::factory()
//            ->create([
//                'user_id' => $user->id,
//                'role' => GlobalRole::ADMINISTRATOR,
//            ]);
        DepartmentUser::factory()
            ->create([
                'department_id' => $department->id,
                'user_id' => $user->id,
                'role' => DepartmentRole::WRITE,
            ]);

        $this->visit('/login')
            ->type($user->email, 'email')
            ->type($password, 'password')
            ->press(__('authentication.login'))
            ->see(__('authentication.one_time_password.code'))
            ->type($code, 'code')
            ->press(__('general.send'))
            ->see(sprintf('<h1>%s</h1>', __('petition.model_plural')));
    }
}
