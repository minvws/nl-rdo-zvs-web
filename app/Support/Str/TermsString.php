<?php

declare(strict_types=1);

namespace App\Support\Str;

use App\Enums\TermType;

use function __;
use function array_map;
use function implode;
use function lcfirst;

final class TermsString
{
    /**
     * @param array<TermType> $terms
     */
    public static function format(array $terms): string
    {
        $names = array_map(
            static fn(TermType $term): string => lcfirst($term->label()),
            $terms,
        );

        return implode(' ' . __('general.or') . ' ', $names);
    }
}
