<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\testfiles;

#[Attribute1]
class ForbiddenAttributes
{
    public function __construct(
        #[Attribute2]
        public int $someProperty = 1,
    )
    {
    }

    #[Attribute2]
    public function someMethod(): void
    {
    }
}

#[Attribute]
class Attribute1
{
}

#[Attribute]
class Attribute2
{
}
