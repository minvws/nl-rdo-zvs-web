<?php

declare(strict_types=1);

namespace App\Facades;

use App\Http\Requests\SortHelper;
use BackedEnum;
use Illuminate\Support\Facades\Facade;

/**
 * @see SortHelper
 *
 * @method static string getAria(BackedEnum $parameter)
 * @method static string getLink(BackedEnum $parameter)
 */
class Sort extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SortHelper::class;
    }
}
