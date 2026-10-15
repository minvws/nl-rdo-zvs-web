<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TagColor;
use App\Models\Department;
use App\Models\Tag;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /** @var class-string<Tag> $model */
    protected $model = Tag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => $this->faker->unique()->words(2, true),
            'color' => $this->faker->randomElement(TagColor::cases()),
            'description' => $this->faker->sentence(),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }
}
