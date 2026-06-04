<?php

declare(strict_types=1);

use App\Enums\RouteName;
use App\Facades\ActiveDepartment;
use Illuminate\Support\Facades\Crypt;

/**
 * @param array<mixed> $parameters
 */
function departmentRoute(string|RouteName $name, array $parameters = []): string
{
    return route($name, array_merge(['department' => ActiveDepartment::getActiveDepartment()], $parameters));
}

function confirmRoute(string $confirmUrl, string $cancelUrl, string $message, string $method = 'POST'): string
{
    return route(RouteName::CONFIRM, [
        'confirm_url' => Crypt::encryptString($confirmUrl),
        'cancel_url' => Crypt::encryptString($cancelUrl),
        'message' => $message,
        'method' => $method,
    ]);
}
