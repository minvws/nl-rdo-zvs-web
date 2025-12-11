<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Str;

use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InitialsTest extends TestCase
{
    #[DataProvider('initialsDataProvider')]
    public function testGetInitialsReturnsCorrectInitialsForFullName(string $input, string $expectedInitials): void
    {
        $this->assertEquals($expectedInitials, Str::initials($input));
    }

    public static function initialsDataProvider(): array
    {
        return [
            ['John Doe', 'JD'],
            ['Nick Öztürk', 'NÖ'],
            ['John', 'J'],
            ["jan de bouvier", "JB"],
            ['', ''],
            ['  John   Doe  ', 'JD'],
            ['John O\'Connor', 'JO'],
            ['John-Doe', 'JD'],
            ['John Smith Doe', 'JD'],
        ];
    }
}
