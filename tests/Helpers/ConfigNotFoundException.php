<?php

declare(strict_types=1);

namespace Tests\Helpers;

use LogicException;

class ConfigNotFoundException extends LogicException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
