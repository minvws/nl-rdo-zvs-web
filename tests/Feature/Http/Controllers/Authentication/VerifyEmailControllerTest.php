<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Authentication;

use App\Enums\RouteName;
use App\Mail\User\EmailVerificationMailable;
use App\Models\Department;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Ramsey\Uuid\UuidInterface;
use Tests\Feature\FeatureTestCase;

class VerifyEmailControllerTest extends FeatureTestCase
{
    public function testSendingEmailVerificationNotification(): void
    {
        Mail::fake();

        $user = User::factory()
            ->fullyVerified()
            ->unverifiedEmail()
            ->create();

        $this->beUser($user)
            ->postByRoute('verification.send')
            ->assertRedirectToRoute('dashboard');

        Mail::assertQueued(EmailVerificationMailable::class);
    }

    public function testNotSendingEmailVerificationNotificationWhenAlreadyVerified(): void
    {
        Mail::fake();

        $user = User::factory()
            ->fullyVerified()
            ->create();

        $this->beUser($user)
            ->postByRoute('verification.send')
            ->assertRedirectToRoute('dashboard');

        Mail::assertNothingSent();
    }

    public function testEmailVerificationWillRender(): void
    {
        Department::factory()->create();
        $user = User::factory()->unverifiedEmail()->create();

        $this->beUser($user)
            ->getByRoute('verification.notice')
            ->assertViewIs('authentication.verify-email');
    }

    public function testRedirectWhenEmailVerified(): void
    {
        $user = User::factory()->fullyVerified()->create();

        $this->beUser($user)
            ->getByRoute('verification.notice')
            ->assertRedirectToRoute('dashboard');
    }

    public function testEmailCanBeVerified(): void
    {
        $user = $this->createUserForRequest(null);

        $verificationUrl = $this->createVerificationUrl($user->id, $user->email);

        $this->get($verificationUrl)
            ->assertRedirectContains('/reset-password')
            ->assertRedirectContains('id=')
            ->assertRedirectContains('token=');

        $user = $this->getUserById($user->id);
        $this->assertNotNull($user->email_verified_at);
    }

    public function testEmailIsNotVerifiedWithInvalidHash(): void
    {
        $user = $this->createUserForRequest(null);

        $verificationUrl = $this->createVerificationUrl($user->id, $this->faker->safeEmail());

        $this->get($verificationUrl);

        $user = $this->getUserById($user->id);
        $this->assertNull($user->email_verified_at);
    }

    public function testRedirectIfUserAlreadyVerified(): void
    {
        $user = $this->createUserForRequest(CarbonImmutable::yesterday());

        $verificationUrl = $this->createVerificationUrl($user->id, $user->email);

        $this->get($verificationUrl)
            ->assertRedirectToRoute(RouteName::LOGIN);
    }

    public function testRedirectIfUserNotFound(): void
    {
        $user = $this->createUserForRequest(null);

        $verificationUrl = $this->createVerificationUrl($this->faker->uuid(), $user->email);

        $this->get($verificationUrl)
            ->assertNotFound();
    }

    private function createUserForRequest(?CarbonImmutable $emailVerifiedAt): User
    {
        if ($emailVerifiedAt === null) {
            return User::factory()
                ->fullyVerified()
                ->unverifiedEmail()
                ->create();
        }

        return User::factory()
            ->fullyVerified()
            ->state(['email_verified_at' => $emailVerifiedAt])
            ->create();
    }

    private function createVerificationUrl(UuidInterface $id, string $email): string
    {
        return URL::temporarySignedRoute('verification.verify', CarbonImmutable::now()->addMinutes(60), [
            'user' => $id,
            'hash' => Hash::make($email),
        ]);
    }
}
