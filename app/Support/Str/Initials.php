<?php

declare(strict_types=1);

namespace App\Support\Str;

use Closure;

use function assert;
use function count;
use function explode;
use function mb_strtoupper;
use function mb_substr;
use function preg_replace;
use function str_replace;
use function trim;

class Initials
{
    public function __invoke(): Closure
    {
        return static function (string $input): string {
            $initials = '';
            $input = trim($input);

            if ($input === '' || $input === '0') {
                return $initials;
            }

            $input = str_replace('-', ' ', $input);
            $input = preg_replace('/\s+/', ' ', $input);

            assert($input !== null);

            /** @var array<string> $words */
            $words = explode(' ', $input);
            $first = $words[0];
            $initials .= mb_substr($first, 0, 1);

            $wordCount = count($words);
            if ($wordCount > 1) {
                $last = $words[$wordCount - 1];
                $initials .= mb_substr($last, 0, 1);
            }

            return mb_strtoupper($initials);
        };
    }
}
