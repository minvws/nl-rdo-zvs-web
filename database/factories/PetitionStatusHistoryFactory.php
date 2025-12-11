<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use Illuminate\Support\Facades\Config;

/**
 * @extends Factory<PetitionStatusHistory>
 */
class PetitionStatusHistoryFactory extends Factory
{
    /** @var class-string<PetitionStatusHistory> $model */
    protected $model = PetitionStatusHistory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tz = Config::string('app.display_timezone');
        $createdAt = $this->faker->dateTime(timezone: 'UTC');
        $tzDate = $createdAt->setTimezone($tz);

        return [
            'petition_id' => Petition::factory(),
            'petition_status_id' => PetitionStatus::factory(),
            'created_at' => $createdAt,
            'date' => $tzDate->format('Y-m-d'),
        ];
    }
}
