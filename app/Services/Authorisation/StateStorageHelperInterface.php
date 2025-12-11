<?php

declare(strict_types=1);

namespace App\Services\Authorisation;

interface StateStorageHelperInterface
{
    public function storeDepartmentSlug(string $slug): void;

    public function getDepartmentSlug(): string;
}
