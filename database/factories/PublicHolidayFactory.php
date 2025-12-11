<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PublicHoliday;

/**
 * @extends Factory<PublicHoliday>
 */
class PublicHolidayFactory extends Factory
{
    /** @var class-string<PublicHoliday> $model */
    protected $model = PublicHoliday::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'name' => $this->faker->word(),
            'date' => $this->faker->date(),
        ];
    }
}
