<?php

declare(strict_types=1);

namespace App\Config;

use Webmozart\Assert\Assert;

use function config;
use function gettype;
use function sprintf;

final class Config
{
    /**
     * @return array<array-key, mixed>
     */
    public static function array(string $key): array
    {
        $configValue = self::get($key);

        Assert::isArray($configValue, self::makeMessage('array', $key, $configValue));

        return $configValue;
    }

    /**
     * @return array<array-key, string>
     */
    public static function arrayAllString(string $key): array
    {
        $configValue = self::get($key);

        Assert::isArray($configValue, self::makeMessage('array', $key, $configValue));
        Assert::allString($configValue, self::makeMessage('array of strings', $key, $configValue));

        return $configValue;
    }

    public static function boolean(string $key): bool
    {
        $configValue = self::get($key);

        Assert::boolean($configValue, self::makeMessage('boolean', $key, $configValue));

        return $configValue;
    }

    public static function integer(string $key): int
    {
        $configValue = self::get($key);

        Assert::numeric($configValue, self::makeMessage('numeric', $key, $configValue));

        return (int) $configValue;
    }

    public static function string(string $key, string $default = ''): string
    {
        $configValue = self::get($key, $default);

        Assert::string($configValue, self::makeMessage('string', $key, $configValue));

        return $configValue;
    }

    public static function stringOrNull(string $key, ?string $default = null): ?string
    {
        $configValue = self::get($key, $default);

        Assert::nullOrString($configValue, self::makeMessage('?string', $key, $configValue));

        return $configValue;
    }

    private static function get(string $value, mixed $default = null): mixed
    {
        return config($value, $default);
    }

    private static function makeMessage(string $method, string $configKey, mixed $value): string
    {
        return sprintf(
            'Expected config key %s to have a value of type %s; got %s',
            $configKey,
            $method,
            gettype($value),
        );
    }
}
