<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\PetitionEvent\Contracts\UpdatePetitionTotalsFromEventsActionInterface;
use App\Actions\PetitionEvent\Contracts\UpdatePetitionTotalsFromTermsActionInterface;
use App\Actions\PetitionEvent\UpdatePetitionTotalsFromEventsAction;
use App\Actions\PetitionEvent\UpdatePetitionTotalsFromTermsAction;
use App\Models\ApiUser;
use App\Models\Decision;
use App\Models\Petition;
use App\Models\User;
use App\Policies\DatabaseNotificationPolicy;
use App\Services\Authentication\AuthenticationService;
use App\Services\Authentication\AuthenticationServiceInterface;
use App\Services\Petition\PetitionNumberGenerator;
use App\Services\Petition\PetitionNumberGeneratorInterface;
use App\Services\Virusscanner\VirusscannerInterface;
use App\Services\Virusscanner\VirusscannerManager;
use App\Support\Str\AddressString;
use App\Support\Str\Initials;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Override;
use Webmozart\Assert\Assert;

use function class_exists;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        Paginator::defaultView('pagination::default');
        Paginator::defaultSimpleView('pagination::simple-default');

        $this->registerStringMacro('address', AddressString::class);
        $this->registerStringMacro('customInitials', Initials::class);

        Model::shouldBeStrict();
        Model::unguard();
        Model::automaticallyEagerLoadRelationships();

        Relation::enforceMorphMap([
            'user' => User::class,
            'petition' => Petition::class,
            'decision' => Decision::class,
            'api_user' => ApiUser::class,
        ]);

//        Lang::handleMissingKeysUsing(static function (string $key): string {
//            // @codeCoverageIgnoreStart
//            $keyPrefixesToIgnore = [
//                "validation.custom",
//                "validation.values",
//            ];
//
//            if (
//                app()->environment('local')
//                && !preg_match('/^(' . implode('|', $keyPrefixesToIgnore) . ')/', $key)
//                && str_contains($key, '.')
//            ) {
//                throw new RuntimeException(sprintf('Missing translation key: %s', $key));
//            }
//
//            // @codeCoverageIgnoreEnd
//            return $key;
//        });

        Gate::policy(DatabaseNotification::class, DatabaseNotificationPolicy::class);
    }

    #[Override]
    public function register(): void
    {
        // @codeCoverageIgnoreStart
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
        // @codeCoverageIgnoreEnd

        $this->app->bind(PetitionNumberGeneratorInterface::class, PetitionNumberGenerator::class);

        $this->app->bind(AuthenticationServiceInterface::class, AuthenticationService::class);

        $this->app->bind(UpdatePetitionTotalsFromEventsActionInterface::class, UpdatePetitionTotalsFromEventsAction::class);
        $this->app->bind(UpdatePetitionTotalsFromTermsActionInterface::class, UpdatePetitionTotalsFromTermsAction::class);


        $this->app->bind(static function (Application $application): VirusscannerInterface {
            /** @var VirusscannerManager $virusscannerManager */
            $virusscannerManager = $application->get(VirusscannerManager::class);
            $virusscanner = $virusscannerManager->driver(Config::string('virusscanner.default'));
            Assert::isInstanceOf($virusscanner, VirusscannerInterface::class);

            return $virusscanner;
        });
    }

    /**
     * @param class-string $abstract
     *
     * @throws BindingResolutionException
     */
    private function registerStringMacro(string $name, string $abstract): void
    {
        Str::macro($name, $this->app->make($abstract)());
        Stringable::macro($name, $this->app->make($abstract)());
    }
}
