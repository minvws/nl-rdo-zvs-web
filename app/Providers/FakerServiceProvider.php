<?php

declare(strict_types=1);

namespace App\Providers;

use App\Config\Config;
use App\Faker\DateTimeProvider;
use App\Faker\PasswordProvider;
use App\Faker\UuidProvider;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\ServiceProvider;
use Override;

class FakerServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $concreteClassClosure = static function (): Generator {
            $faker = Factory::create(Config::string('app.faker_locale'));
            $faker->addProvider(new DateTimeProvider($faker));
            $faker->addProvider(new UuidProvider($faker));

            $maxLength = Config::integer('auth.passwords.maximum_length');
            $minLength = Config::integer('auth.passwords.minimum_length');
            $faker->addProvider(new PasswordProvider($faker, $minLength, $maxLength));

            return $faker;
        };

        $generator = Generator::class;
        $this->app->singleton($generator, $concreteClassClosure);
    }
}
