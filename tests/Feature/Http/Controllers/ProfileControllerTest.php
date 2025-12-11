<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class ProfileControllerTest extends FeatureTestCase
{
    public function testProfilePageIsDisplayed(): void
    {
        Department::factory()->create();

        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user)
            ->getByRoute('profile.edit')
            ->assertOk();
    }

    public function testProfileInformationCanBeUpdated(): void
    {
        $user = User::factory()
            ->fullyVerified()
            ->create();
        $name = $this->faker->name();

        $response = $this->beUser($user)
            ->postByRoute('profile.edit', [
                'name' => $name,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute('profile.edit');

        $user->refresh();
        $this->assertSame($name, $user->name);
    }

    public function testProfilePageIsAccessibleWhenOtpDisabled(): void
    {
        Department::factory()->create();
        $user = User::factory()
            ->fullyVerified()
            ->otpDisabled()
            ->create();

        $this->beUser($user)
            ->getByRoute('profile.edit')
            ->assertStatus(302);
        $this->beUser($user)
            ->getByRoute('one-time-password.enroll')
            ->assertOk();
    }

    public function testProfilePageIsAccessibleWhenOtpEnabledButNotVerified(): void
    {
        Department::factory()->create();
        $user = User::factory()
            ->fullyVerified()
            ->state(['otp_verified_at' => null])
            ->create();

        $this->beUser($user)
            ->getByRoute('profile.edit')
            ->assertStatus(302);
        $this->beUser($user)
            ->getByRoute('one-time-password.enroll')
            ->assertOk();
    }
}
