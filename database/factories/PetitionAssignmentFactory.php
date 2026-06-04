<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AssignmentRole;
use App\Models\Petition;
use App\Models\PetitionAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PetitionAssignment>
 */
class PetitionAssignmentFactory extends Factory
{
    /** @var class-string<PetitionAssignment> $model */
    protected $model = PetitionAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'petition_id' => Petition::factory(),
            'user_id' => User::factory(),
            'assignment_role' => AssignmentRole::PRIMARY,
        ];
    }
}
