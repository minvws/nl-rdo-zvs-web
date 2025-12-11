<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExportType;
use App\Models\Department;
use App\Models\PetitionCategory;
use App\Models\PetitionExport;
use App\Models\PetitionType;

use function json_encode;

/**
 * @extends Factory<PetitionExport>
 */
class PetitionExportFactory extends Factory
{
    /** @var class-string<PetitionExport> $model */
    protected $model = PetitionExport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $disk = 'exports';

        return [

            'department_id' => Department::factory(),
            'petition_type_id' => PetitionType::factory(),
            'petition_category_id' => $this->faker->randomElement([
                PetitionCategory::factory(),
                null,
            ]),
            'date_from' => $this->faker->calendarDate(),
            'date_to' => $this->faker->calendarDate(),
            'filters' => json_encode([]),
            'type' => $this->faker->randomElement(ExportType::cases()),
            'disk' => $disk,
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),
        ];
    }
}
