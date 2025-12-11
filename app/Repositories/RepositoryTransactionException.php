<?php

declare(strict_types=1);

namespace App\Repositories;

use Throwable;

use function is_int;

class RepositoryTransactionException extends RepositoryException
{
    public static function fromThrowable(Throwable $throwable): self
    {
        return new self($throwable->getMessage(), is_int($throwable->getCode()) ? $throwable->getCode() : 0, $throwable);
    }
}
