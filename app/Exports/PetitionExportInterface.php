<?php

declare(strict_types=1);

namespace App\Exports;

interface PetitionExportInterface
{
    public function writeToDisk(string $path, string $disk): void;
}
