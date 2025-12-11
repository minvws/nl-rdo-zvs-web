<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class NotFoundException extends NotFoundHttpException
{
    public static function fromThrowable(string $message, Throwable $throwable): self
    {
        return new self($message, $throwable);
    }
}
