<?php

declare(strict_types=1);

namespace App\Providers;

use App\Config\Config;
use App\Services\Authentication\OneTimePassword\OneTimePasswordInterface;
use App\Services\Authentication\OneTimePassword\OneTimePasswordManager;
use App\Services\Authentication\OneTimePasswordService;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Override;

class AuthenticationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Password::defaults(static function () {
            return Password::min(Config::integer('auth.passwords.minimum_length'))
                ->max(Config::integer('auth.passwords.maximum_length'))
                ->uncompromised();
        });
    }

    #[Override]
    public function register(): void
    {
        $this->app->singleton(static function (Application $application): OneTimePasswordInterface {
            /** @var OneTimePasswordManager $oneTimePasswordManager */
            $oneTimePasswordManager = $application->get(OneTimePasswordManager::class);

            /** @var OneTimePasswordInterface $oneTimePassword */
            $oneTimePassword = $oneTimePasswordManager->driver(Config::string('auth.one_time_password.driver'));

            return $oneTimePassword;
        });

        $this->app->when(OneTimePasswordService::class)
            ->needs('$qrCodeLabelPrefix')
            ->giveConfig('app.name');
    }
}
