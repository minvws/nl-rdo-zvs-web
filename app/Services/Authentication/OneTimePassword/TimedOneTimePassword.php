<?php

declare(strict_types=1);

namespace App\Services\Authentication\OneTimePassword;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use OTPHP\TOTP;
use Webmozart\Assert\Assert;

use function strpos;
use function substr;
use function trim;

class TimedOneTimePassword implements OneTimePasswordInterface
{
    /**
     * @param non-empty-string $code
     */
    public function isCodeValid(string $code, string $secret): bool
    {
        Assert::stringNotEmpty($secret);
        $totp = TOTP::createFromSecret($secret);

        return $totp->verify($code);
    }

    public function generateSecretKey(): string
    {
        return TOTP::generate()->getSecret();
    }

    /**
     * @param non-empty-string $label
     * @param non-empty-string $secret
     */
    public function generateQRCodeInline(string $label, string $secret): string
    {
        $otp = TOTP::createFromSecret($secret);
        $otp->setLabel($label);
        $url = $otp->getProvisioningUri();

        $fill = Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(0, 0, 0));
        $rendererStyle = new RendererStyle(150, 1, null, null, $fill);
        $renderer = new ImageRenderer($rendererStyle, new SvgImageBackEnd());

        $svg = (new Writer($renderer))
            ->writeString($url);

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }
}
