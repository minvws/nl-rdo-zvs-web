<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PasswordResetToken;

/**
 * @extends Factory<PasswordResetToken>
 */
class PasswordResetTokenFactory extends Factory
{
    /** @var class-string<PasswordResetToken> $model */
    protected $model = PasswordResetToken::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'email' => $this->faker->unique()->safeEmail(),
            'token' => $this->faker->password(),
            'created_at' => $this->faker->dateTime(),
        ];
    }
}
