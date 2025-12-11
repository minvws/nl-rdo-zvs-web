<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Authorization\DepartmentRole;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\User;

/**
 * @extends Factory<DepartmentUser>
 */
class DepartmentUserFactory extends Factory
{
    /** @var class-string<DepartmentUser> $model */
    protected $model = DepartmentUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'user_id' => User::factory(),
            'role' => $this->faker->randomElement(DepartmentRole::cases()),
        ];
    }
}
