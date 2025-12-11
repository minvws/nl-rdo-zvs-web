<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExternalUrlType;
use App\Models\Petition;
use App\Models\PetitionExternalUrl;

/**
 * @extends Factory<PetitionExternalUrl>
 */
class PetitionExternalUrlFactory extends Factory
{
    /** @var class-string<PetitionExternalUrl> $model */
    protected $model = PetitionExternalUrl::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'petition_id' => Petition::factory(),
            'petition_external_url_type' => $this->faker->randomElement(ExternalUrlType::cases()),
            'url' => $this->faker->url(),
        ];
    }
}
