<?php

declare(strict_types=1);

namespace App\Facades;

use App\Http\Requests\FormHelper;
use Illuminate\Support\Facades\Facade;

/**
 * @see FormHelper
 *
 * @method static string|null old(string $key, string|int|null $default = null)
 * @method static array<string, mixed>|null oldArray(string $key, string|int|null $default = null)
 */
class Form extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FormHelper::class;
    }
}
