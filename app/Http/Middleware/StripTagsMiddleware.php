<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Override;

use function array_merge;
use function is_string;
use function strip_tags;

class StripTagsMiddleware extends TrimStrings
{
    /**
     * The attributes that should not be stripped of tags.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api_key',
        'api_secret',
        'current_password',
        'hash',
        'password',
        'password_confirmation',
        'token',
    ];

    /**
     * Transform the given value.
     *
     * @param string $key
     * @param mixed $value
     */
    #[Override]
    protected function transform($key, $value): mixed
    {
        $except = array_merge($this->except, static::$neverTrim);

        if ($this->shouldSkip($key, $except) || !is_string($value)) {
            return $value;
        }

        return strip_tags($value);
    }
}
