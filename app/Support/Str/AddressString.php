<?php

declare(strict_types=1);

namespace App\Support\Str;

use App\ValueObjects\Address;
use Closure;

use function implode;
use function sprintf;
use function trim;

use const PHP_EOL;

class AddressString
{
    public function __invoke(): Closure
    {
        return static function (Address $input): string {
            $address = [];
            if ($input->organisationName !== null && $input->lastName !== null) {
                $address[] = $input->organisationName;
                $address[] = trim(sprintf('T.a.v. %s', trim(sprintf('%s %s', $input->initials, $input->lastName))));
            } elseif ($input->organisationName !== null) {
                $address[] = $input->organisationName;
            } elseif ($input->lastName !== null) {
                $address[] = ($input->initials !== null ? $input->initials . ' ' : '') . $input->lastName;
            }

            if ($input->street !== null) {
                $address[] = trim(sprintf('%s %s', $input->street, $input->houseNumber));
            }

            if ($input->postalCode !== null && $input->city !== null) {
                $address[] = sprintf('%s %s', $input->postalCode, $input->city);
            }

            return implode(PHP_EOL, $address);
        };
    }
}
