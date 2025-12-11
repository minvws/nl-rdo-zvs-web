<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApiUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiUser>
 */
class ApiUserFactory extends Factory
{
    /** @var class-string<ApiUser> $model */
    protected $model = ApiUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'api_key' => Str::random(64),
            'api_secret' => Hash::make(Str::random(128)),
        ];
    }
}
