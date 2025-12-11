<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\Authorization\GlobalRole;
use App\Enums\Authorization\Permission;
use App\Models\Department;
use App\Models\User;
use App\Models\UserGlobalRole;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\Helpers\ConfigHelper;

use function collect;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /** @var class-string<User> $model */
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'active' => true,
            'email_verified_at' => $this->faker->optional()->dateTime(),
            'password' => $this->faker->optional()->passthrough(Hash::make($this->faker->password())),
            'otp_verified_at' => $this->faker->optional()->dateTime(),
            'otp_secret' => $this->faker->optional()->passthrough(Crypt::encrypt($this->faker->word())),
            'remember_token' => $this->faker->optional()->sha256(),
        ];
    }

    public function fullyVerified(): self
    {
        return $this->state(function (): array {
            return [
                'email_verified_at' => CarbonImmutable::yesterday(),
                'password' => Hash::make($this->faker->password()),
                'otp_verified_at' => CarbonImmutable::yesterday(),
                'otp_secret' => Crypt::encrypt($this->faker->word()),
            ];
        });
    }

    public function withDepartmentRoles(Department $department, DepartmentRole ...$roles): self
    {
        return $this->afterCreating(function (User $user) use ($roles, $department): void {
            foreach ($roles as $role) {
                $user->departments()->attach($department, ['role' => $role->value]);
            }
        });
    }

    public function withGlobalRoles(GlobalRole ...$globalRoles): self
    {
        return $this->afterCreating(function (User $user) use ($globalRoles): void {
            foreach ($globalRoles as $globalRole) {
                UserGlobalRole::factory()->create([
                    'role' => $globalRole,
                    'user_id' => $user->id,
                ]);
            }
        });
    }

    public function asAdministrator(): self
    {
        return $this->fullyVerified()->withGlobalRoles(GlobalRole::ADMINISTRATOR);
    }

    public function withLastVisitedDepartment(Department $department): self
    {
        return $this->state(['last_visited_department_id' => $department->id]);
    }

    public function active(bool $active = true): self
    {
        return $this->state(['active' => $active]);
    }

    public function inactive(): self
    {
        return $this->active(false);
    }

    public function unverifiedEmail(): self
    {
        return $this->state(['email_verified_at' => null]);
    }

    // === OTP state methods ===

    public function otpDisabled(): self
    {
        return $this->state([
            'otp_secret' => null,
            'otp_verified_at' => null,
        ]);
    }

    // === Password state methods ===

    public function withHashedPassword(string $hashedPassword): self
    {
        return $this->state(['password' => $hashedPassword]);
    }

    // === Single role convenience method ===

    public function withGlobalRole(GlobalRole $role): self
    {
        return $this->withGlobalRoles($role);
    }

    // === Permission-based convenience methods ===

    /**
     * Creates a user with administrator role and configures test permissions.
     * This replaces the beAuthorizedUserWithPermission pattern.
     * Note: Does NOT set fullyVerified - call explicitly if needed.
     */
    public function withPermissions(Permission ...$permissions): self
    {
        return $this->withGlobalRoles(GlobalRole::ADMINISTRATOR)
            ->afterCreating(function () use ($permissions): void {
                $permissionValues = collect($permissions)->map(fn(Permission $permission): string => $permission->value);
                ConfigHelper::set('permissions.roles_and_permissions', [
                    GlobalRole::ADMINISTRATOR->value => $permissionValues->toArray(),
                ]);
            });
    }

    /**
     * Creates a user with permissions and sets up department context.
     * This replaces the beAuthorizedUserWithPermissionAndDepartment pattern.
     * Note: Does NOT set fullyVerified - call explicitly if needed.
     */
    public function withPermissionsAndDepartment(?Department $department = null, Permission ...$permissions): self
    {
        $targetDepartment = $department ?? Department::factory()->create();

        return $this->withPermissions(...$permissions)
            ->withLastVisitedDepartment($targetDepartment)
            ->afterCreating(function (): void {
                // This will be handled in the beUser method with department parameter
            });
    }
}
