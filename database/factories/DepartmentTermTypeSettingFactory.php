<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TermType;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;

/**
 * @extends Factory<DepartmentTermTypeSetting>
 */
class DepartmentTermTypeSettingFactory extends Factory
{
    /** @var class-string<DepartmentTermTypeSetting> $model */
    protected $model = DepartmentTermTypeSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'department_id' => Department::factory(),
            'term_type' => $this->faker->randomElement(TermType::cases()),
            'field' => $this->faker->word(),
            'active' => $this->faker->boolean(),
            'default_value' => $this->faker->optional()->numberBetween(0, 100),
            'title' => $this->faker->optional()->word(),
        ];
    }
}
