<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Authorization\GlobalRole;
use App\Models\User;
use App\Models\UserGlobalRole;
use Illuminate\Support\Arr;

/**
 * @extends Factory<UserGlobalRole>
 */
class UserGlobalRoleFactory extends Factory
{
    /** @var class-string<UserGlobalRole> $model */
    protected $model = UserGlobalRole::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var GlobalRole $role */
        $role = Arr::random(GlobalRole::cases());

        return [

            'user_id' => User::factory(),
            'role' => $role,
        ];
    }
}
