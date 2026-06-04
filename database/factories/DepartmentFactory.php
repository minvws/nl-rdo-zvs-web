<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Support\Arr;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /** @var class-string<Department> $model */
    protected $model = Department::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hideColumnOptions = ['zaaksoort', 'categorie', 'status', 'indiener'];

        return [
            'name' => $this->faker->company(),
            'slug' => $this->faker->unique()->slug(),
            'config_key' => $this->faker->word(),
            'abbreviation' => $this->faker->regexify('[A-Z]{1,3}'),
            'hide_column_defaults' => Arr::join($this->faker->randomElements($hideColumnOptions, $this->faker->numberBetween(0, 3)), ','),
        ];
    }
}
