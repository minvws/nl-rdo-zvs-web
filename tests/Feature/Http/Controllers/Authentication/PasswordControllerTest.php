<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Authentication;

use App\Enums\RouteName;
use App\Mail\User\PasswordResetMailable;
use App\Models\PasswordResetToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

use function __;

class PasswordControllerTest extends FeatureTestCase
{
    public function testForgotPasswordLinkScreenCanBeRendered(): void
    {
        $this->getByRoute(RouteName::FORGOT_PASSWORD_REQUEST)
            ->assertStatus(200);
    }

    public function testResetPasswordRequestWhenIdNotSet(): void
    {
        $this->getByRoute('password.reset.request')
            ->assertRedirectToRoute(RouteName::LOGIN);
    }

    public function testResetPasswordRequestWhenIdNotFound(): void
    {
        $this->getByRoute('password.reset.request', [
            'id' => $this->faker->uuid()->toString(),
            'token' => $this->faker->uuid()->toString(),
        ])
            ->assertNotFound();
    }

    public function testResetPasswordRequestWhenTokenNotSet(): void
    {
        $passwordResetToken = PasswordResetToken::factory()->create([
            'created_at' => CarbonImmutable::now(),
        ]);

        $this->getByRoute('password.reset.request', [
            'id' => $passwordResetToken->id->toString(),
        ])
            ->assertRedirectToRoute(RouteName::LOGIN);
    }

    public function testResetPasswordRequestWhenTokenNotFound(): void
    {
        $passwordResetToken = PasswordResetToken::factory()->create([
            'created_at' => CarbonImmutable::now(),
        ]);

        $this->getByRoute('password.reset.request', [
            'id' => $passwordResetToken->id->toString(),
            'token' => $this->faker->uuid()->toString(),
        ])
            ->assertNotFound();
    }

    public function testResetPasswordRequestWhenTokenExpired(): void
    {
        $passwordResetToken = PasswordResetToken::factory()->create([
            'created_at' => CarbonImmutable::now()->subHours(2),
        ]);

        $this->getByRoute('password.reset.request', [
            'id' => $passwordResetToken->id->toString(),
            'token' => $passwordResetToken->token,
        ])
            ->assertNotFound();
    }

    public function testResetPasswordRequestWhenIdFound(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $passwordResetToken = PasswordResetToken::factory()->create([
            'email' => $user->email,
            'created_at' => CarbonImmutable::now(),
        ]);

        $response = $this->getByRoute('password.reset.request', [
            'id' => $passwordResetToken->id->toString(),
            'token' => $passwordResetToken->token,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('authentication.reset-password');
        $response->assertViewHas('id', $passwordResetToken->id);
        $response->assertViewHas('email', $user->email);
        $response->assertViewHas('token', $passwordResetToken->token);
    }

    public function testResetPasswordRequestWhenIdInvalidUuid(): void
    {
        $this->getByRoute('password.reset.request', [
            'id' => 'invalid-uuid',
            'token' => $this->faker->uuid()->toString(),
        ])
            ->assertNotFound();
    }

    public function testResetPasswordScreenCanBeRendered(): void
    {
        Mail::fake();

        $user = User::factory()->fullyVerified()->create();

        $this->postByRoute(RouteName::FORGOT_PASSWORD_EMAIL, data: ['email' => $user->email]);

        Mail::assertQueued(PasswordResetMailable::class, function (PasswordResetMailable $mailable): bool {
            $this->get($mailable->link)
                ->assertStatus(200);

            return true;
        });
    }

    public function testPasswordCanBeResetWithValidToken(): void
    {
        Mail::fake();

        $user = User::factory()->fullyVerified()->create();
        $password = 'super-random-password-not-leaked';

        $this->mock(UncompromisedVerifier::class, static function (MockInterface $mock) use ($password): void {
            $mock->expects('verify')
                ->with([
                    'threshold' => 0,
                    'value' => $password,
                ])
                ->andReturnTrue();
        });

        $this->postByRoute(RouteName::FORGOT_PASSWORD_EMAIL, ['email' => $user->email]);

        Mail::assertQueued(PasswordResetMailable::class, function (PasswordResetMailable $mailable) use ($password): bool {
            $this->post($mailable->link, [
                'password' => $password,
                'password_confirmation' => $password,
            ])
                ->assertSessionHasNoErrors()
                ->assertRedirectToRoute(RouteName::LOGIN);

            return true;
        });
    }

    public function testPasswordResetWithValidTokenButUserDeleted(): void
    {
        Mail::fake();

        $user = User::factory()->fullyVerified()->create();
        $password = 'super-random-password-not-leaked';

        $this->mock(UncompromisedVerifier::class, static function (MockInterface $mock) use ($password): void {
            $mock->expects('verify')
                ->with([
                    'threshold' => 0,
                    'value' => $password,
                ])
                ->andReturnTrue();
        });

        $this->postByRoute(RouteName::FORGOT_PASSWORD_EMAIL, ['email' => $user->email]);

        Mail::assertQueued(PasswordResetMailable::class, function (PasswordResetMailable $mailable) use ($user, $password): bool {
            User::query()
                ->where('id', '=', $user->id)
                ->delete();

            $this->post($mailable->link, [
                'password' => $password,
                'password_confirmation' => $password,
            ])->assertNotFound();

            return true;
        });
    }

    public function testPasswordResetStoreAlsoDeletesToken(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $passwordResetToken = PasswordResetToken::factory()->create([
            'created_at' => CarbonImmutable::now(),
            'email' => $user->email,
        ]);
        $newPassword = $this->faker->password();

        $this->mock(UncompromisedVerifier::class, static function (MockInterface $mock) use ($newPassword): void {
            $mock->expects('verify')
                ->with([
                    'threshold' => 0,
                    'value' => $newPassword,
                ])
                ->andReturnTrue();
        });

        $this->postByRoute('password.reset.store', [
            'id' => $passwordResetToken->id->toString(),
            'token' => $passwordResetToken->token,
        ], [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])
            ->assertRedirect();

        $this->assertDatabaseMissing(PasswordResetToken::class, [
            'id' => $passwordResetToken->id,
        ]);
    }

    public function testPasswordResetStoreWhenIdInvalid(): void
    {
        $password = $this->faker->password();

        $this->mock(UncompromisedVerifier::class, static function (MockInterface $mock) use ($password): void {
            $mock->expects('verify')
                ->with([
                    'threshold' => 0,
                    'value' => $password,
                ])
                ->andReturnTrue();
        });

        $this->postByRoute(
            'password.reset.store',
            ['id' => $this->faker->word(), 'token' => $this->faker->uuid()->toString()],
            ['password' => $password, 'password_confirmation' => $password],
        )
            ->assertNotFound();
    }

    public function testPasswordResetStoreWhenIdNotFound(): void
    {
        $password = $this->faker->password();

        $this->mock(UncompromisedVerifier::class, static function (MockInterface $mock) use ($password): void {
            $mock->expects('verify')
                ->with([
                    'threshold' => 0,
                    'value' => $password,
                ])
                ->andReturnTrue();
        });

        $this->postByRoute(
            'password.reset.store',
            ['id' => $this->faker->uuid()->toString(), 'token' => $this->faker->uuid()->toString()],
            ['password' => $password, 'password_confirmation' => $password],
        )
            ->assertNotFound();
    }

    public function testResetPasswordLinkCanBeRequested(): void
    {
        Mail::fake();

        $user = User::factory()->fullyVerified()->create();

        $this->postByRoute(RouteName::FORGOT_PASSWORD_EMAIL, ['email' => $user->email]);

        Mail::assertQueued(PasswordResetMailable::class, function (PasswordResetMailable $mailable): bool {
            $this->get($mailable->link)
                ->assertStatus(200);

            $mailable->hasSubject(__('user.mail.password_reset.subject'));
            $mailable->assertSeeInText(__('user.mail.password_reset.text'));

            return true;
        });
    }

    public function testResetPasswordShowsNoErrorWhenUnknownEmailAddress(): void
    {
        $this->postByRoute(RouteName::FORGOT_PASSWORD_EMAIL, data: ['email' => $this->faker->safeEmail()])
            ->assertRedirectToRoute(RouteName::LOGIN);
    }

    public function testPasswordCanBeUpdated(): void
    {
        $password = $this->faker->password();
        $user = User::factory()->fullyVerified()->withHashedPassword(Hash::make($password))->create();
        $newPassword = $this->faker->unique()->password();

        $this->mock(UncompromisedVerifier::class, static function (MockInterface $mock) use ($newPassword): void {
            $mock->expects('verify')
                ->with([
                    'threshold' => 0,
                    'value' => $newPassword,
                ])
                ->andReturnTrue();
        });

        $this->beUser($user)
            ->fromRoute('profile.edit')
            ->putByRoute(RouteName::PASSWORD_UPDATE, data: [
                'current_password' => $password,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::LOGIN);

        $result = $this->getUserById($user->id);
        $this->assertDatabaseMissing('sessions', ['user_id' => $result->id]);
        $this->assertTrue(Hash::check($newPassword, $result->password));
    }

    public function testCorrectPasswordMustBeProvidedToUpdatePassword(): void
    {
        $newPassword = 'new-password';

        $this->mock(UncompromisedVerifier::class, static function (MockInterface $mock) use ($newPassword): void {
            $mock->expects('verify')
                ->with([
                    'threshold' => 0,
                    'value' => $newPassword,
                ])
                ->andReturnTrue();
        });

        $currentPassword = $this->faker->password();
        $user = User::factory()->fullyVerified()->withHashedPassword(Hash::make($currentPassword))->create();

        $this->beUser($user)
            ->fromRoute('profile.edit')
            ->putByRoute(RouteName::PASSWORD_UPDATE, data: [
                'current_password' => 'wrong-password',
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirectToRoute('profile.edit');
    }

    #[DataProvider('generateInvalidPasswords')]
    public function testPasswordLengthValidation(string $newPassword): void
    {
        $this->mock(UncompromisedVerifier::class, static function (MockInterface $mock): void {
            $mock->expects('verify')
                ->times(0);
        });

        $password = $this->faker->password();
        $user = User::factory()->fullyVerified()->withHashedPassword(Hash::make($password))->create();

        $this->beUser($user)
            ->fromRoute('profile.edit')
            ->putByRoute(RouteName::PASSWORD_UPDATE, data: [
                'current_password' => $password,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password')
            ->assertRedirectToRoute('profile.edit');
    }

    public static function generateInvalidPasswords(): array
    {
        return [
            'can not be empty' => [''],
            'must be > 12 chars' => ['too short'],
            'must be < 128 chars' => ['R*U[9K;v=2C{MyP^B/mWzkaTDLj6~7.`"uXV5b&<Q:8-Fh_Jw@HLy2DJ5rdMpU%_3FQPs!}X:]6Gx4/&~{Acg(;ht,CWm7<`>Z@wSC$`Uhup^xZ-RH)cT#Q!<M5k.q?~L&WYF+tjdJ,"f/>z]:yXf`*2YBQrR7?"(.&xd;D$>evG#S<u}{[KpT5_,!VAUM=jLk6szP'],
        ];
    }

    public function testPasswordValidationErrorWhenCompromised(): void
    {
        $newPassword = 'validLengthButComprimised';

        $this->mock(UncompromisedVerifier::class, static function (MockInterface $mock) use ($newPassword): void {
            $mock->expects('verify')
                ->with([
                    'threshold' => 0,
                    'value' => $newPassword,
                ])
                ->andReturnFalse();
        });

        $password = $this->faker->password();
        $user = User::factory()->fullyVerified()->withHashedPassword(Hash::make($password))->create();

        $this->beUser($user)
            ->fromRoute('profile.edit')
            ->putByRoute(RouteName::PASSWORD_UPDATE, data: [
                'current_password' => $password,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password')
            ->assertRedirectToRoute('profile.edit');
    }
}
