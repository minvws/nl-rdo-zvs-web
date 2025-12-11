<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Str;

use App\ValueObjects\Address;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

use function array_key_exists;
use function sprintf;

use const PHP_EOL;

class AddressStringTest extends TestCase
{
    /**
     * @param array<string, ?string> $input
     */
    #[DataProvider('initialsDataProvider')]
    public function testAddressGeneration(array $input, string $expectedAddress): void
    {
        $address = new Address(
            $this->valueOrNull($input, 'initials'),
            $this->valueOrNull($input, 'lastName'),
            $this->valueOrNull($input, 'organisationName'),
            $this->valueOrNull($input, 'street'),
            $this->valueOrNull($input, 'houseNumber'),
            $this->valueOrNull($input, 'postalCode'),
            $this->valueOrNull($input, 'city'),
        );

        $this->assertEquals($expectedAddress, Str::address($address));
    }

    public static function initialsDataProvider(): array
    {
        return [
            [[], ''],
            [['lastName' => 'Doe'], 'Doe'],
            [['lastName' => 'Doe', 'initials' => 'Mister'], 'Mister Doe'],
            [['lastName' => 'Doe', 'initials' => 'Mister'], 'Mister Doe'],
            [['lastName' => 'Doe', 'initials' => 'Mister', 'street' => 'Highway'], sprintf('Mister Doe%sHighway', PHP_EOL)],
            [
                ['lastName' => 'Doe', 'initials' => 'Mister', 'street' => 'Highway', 'houseNumber' => '61'],
                sprintf('Mister Doe%sHighway 61', PHP_EOL),
            ],
            [
                ['lastName' => 'Doe', 'initials' => 'Mister', 'street' => 'Highway', 'houseNumber' => '61', 'postalCode' => '1234'],
                sprintf('Mister Doe%sHighway 61', PHP_EOL),
            ],
            [
                ['lastName' => 'Doe', 'initials' => 'Mister', 'postalCode' => '1234', 'city' => 'Decatur'],
                sprintf('Mister Doe%s1234 Decatur', PHP_EOL),
            ],
            [
                [
                    'lastName' => 'Doe',
                    'initials' => 'Mister',
                    'street' => 'Highway',
                    'houseNumber' => '61',
                    'postalCode' => '1234',
                    'city' => 'Decatur',
                ],
                sprintf('Mister Doe%sHighway 61%s1234 Decatur', PHP_EOL, PHP_EOL),
            ],
            [['organisationName' => 'ACME'], 'ACME'],
            [['organisationName' => 'ACME', 'lastName' => 'Doe'], sprintf('ACME%sT.a.v. Doe', PHP_EOL)],
            [['organisationName' => 'ACME', 'initials' => 'Mister', 'lastName' => 'Doe'], sprintf('ACME%sT.a.v. Mister Doe', PHP_EOL)],
            [['organisationName' => 'ACME', 'street' => 'Highway', 'houseNumber' => '61'], sprintf('ACME%sHighway 61', PHP_EOL)],
            [
                ['organisationName' => 'ACME', 'street' => 'Highway', 'houseNumber' => '61', 'postalCode' => '1234', 'city' => 'Decatur'],
                sprintf('ACME%sHighway 61%s1234 Decatur', PHP_EOL, PHP_EOL),
            ],
            [['organisationName' => 'ACME', 'postalCode' => '1234', 'city' => 'Decatur'], sprintf('ACME%s1234 Decatur', PHP_EOL)],
        ];
    }

    /**
     * @param array<string, ?string> $input
     */
    private function valueOrNull(array $input, string $key): mixed
    {
        return array_key_exists($key, $input) ? $input[$key] : null;
    }
}
