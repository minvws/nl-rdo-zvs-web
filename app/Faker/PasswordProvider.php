<?php

declare(strict_types=1);

namespace App\Faker;

use Faker\Generator;
use Faker\Provider\Internet;
use Override;

class PasswordProvider extends Internet
{
    public function __construct(
        Generator $generator,
        private readonly int $minimumLength,
        private readonly int $maximumLength,
    ) {
        parent::__construct($generator);
    }

    /**
     * @param int $minLength
     * @param int $maxLength
     */
    #[Override]
    public function password($minLength = 0, $maxLength = 0): string
    {
        if ($minLength === 0) {
            $minLength = $this->minimumLength;
        }
        if ($maxLength === 0) {
            $maxLength = $this->maximumLength;
        }

        return parent::password($minLength, $maxLength);
    }
}
