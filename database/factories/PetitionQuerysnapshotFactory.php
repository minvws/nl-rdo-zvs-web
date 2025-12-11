<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuerysnapshotType;
use App\Models\Petition;
use App\Models\PetitionQuerysnapshot;

/**
 * @extends Factory<PetitionQuerysnapshot>
 */
class PetitionQuerysnapshotFactory extends Factory
{
    /** @var class-string<PetitionQuerysnapshot> $model */
    protected $model = PetitionQuerysnapshot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'petition_id' => Petition::factory(),
            'querysnapshot_id' => $this->faker->word(),
            'querysnapshot_type' => $this->faker->randomElement(QuerysnapshotType::cases()),
        ];
    }
}
