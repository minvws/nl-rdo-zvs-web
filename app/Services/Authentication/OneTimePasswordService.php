<?php

declare(strict_types=1);

namespace App\Services\Authentication;

use App\Events\OtpAuthenticationFailedEvent;
use App\Events\OtpAuthenticationSuccessEvent;
use App\Models\User;
use App\Repositories\SessionRepository;
use App\Services\Authentication\OneTimePassword\OneTimePasswordInterface;
use App\Services\EncryptionService;
use Carbon\CarbonImmutable;
use Ramsey\Uuid\UuidInterface;

use function event;
use function sprintf;

class OneTimePasswordService
{
    private const string SESSION_IDENTIFIER = 'otp_authenticated';

    public function __construct(
        private readonly EncryptionService $encryptionService,
        private readonly OneTimePasswordInterface $oneTimePassword,
        private readonly SessionRepository $sessionRepository,
        private readonly string $qrCodeLabelPrefix,
    ) {
    }

    /**
     * @param object{otp_secret: ?string} $user
     */
    public function getOtpSecret(object $user): ?string
    {
        $encryptedOtpSecret = $user->otp_secret;
        if ($encryptedOtpSecret === null) {
            return null;
        }

        return $this->encryptionService->decrypt($encryptedOtpSecret);
    }

    /**
     * @param object{
     *     email: string,
     *     otp_secret: ?string
     * } $user
     */
    public function generateQRCodeInline(object $user): ?string
    {
        $otpSecret = $this->getOtpSecret($user);
        if ($otpSecret === null) {
            return null;
        }

        $label = sprintf('%s (%s)', $this->qrCodeLabelPrefix, $user->email);

        return $this->oneTimePassword->generateQRCodeInline($label, $otpSecret);
    }

    public function verifyCode(string $code, User $user): bool
    {
        $encryptedOtpSecret = $user->otp_secret;

        if ($encryptedOtpSecret === null) {
            event(new OtpAuthenticationFailedEvent($user, ['email' => $user->email]));

            return false;
        }

        $otpSecret = $this->encryptionService->decrypt($encryptedOtpSecret);
        $isValid = $this->oneTimePassword->isCodeValid($code, $otpSecret);

        if ($isValid === false) {
            event(new OtpAuthenticationFailedEvent($user, ['email' => $user->email]));

            return false;
        }

        $user->update(['otp_verified_at' => CarbonImmutable::now()]);

        $this->setValidSession($user);

        event(new OtpAuthenticationSuccessEvent($user, ['email' => $user->email, 'action' => 'otp_authentication_success']));

        return true;
    }

    /**
     * @param object{id: UuidInterface} $user
     */
    public function isAuthenticated(object $user): bool
    {
        $sessionValue = $this->sessionRepository->get(self::SESSION_IDENTIFIER);
        if (!$sessionValue instanceof UuidInterface) {
            return false;
        }

        return $user->id->equals($sessionValue);
    }

    /**
     * @param object{otp_secret: ?string} $user
     */
    public function hasOtpEnabled(object $user): bool
    {
        return $user->otp_secret !== null;
    }

    /**
     * @param object{
     *     otp_secret: ?string,
     *     otp_verified_at: ?CarbonImmutable
     * } $user
     */
    public function hasOtpVerified(object $user): bool
    {
        if ($user->otp_secret === null) {
            return false;
        }

        $otpVerifiedAt = $user->otp_verified_at;
        if ($otpVerifiedAt === null) {
            return false;
        }

        return !$otpVerifiedAt->isFuture();
    }

    private function setValidSession(User $user): void
    {
        $this->sessionRepository->save(self::SESSION_IDENTIFIER, $user->id);
    }
}
