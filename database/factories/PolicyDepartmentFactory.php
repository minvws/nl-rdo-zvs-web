<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PolicyDepartment;

/**
 * @extends Factory<PolicyDepartment>
 */
class PolicyDepartmentFactory extends Factory
{
    /** @var class-string<PolicyDepartment> $model */
    protected $model = PolicyDepartment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'active' => true,
        ];
    }
}
