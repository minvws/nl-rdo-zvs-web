<?php

declare(strict_types=1);

namespace App\Services\Petition;

use App\Models\Department;

interface PetitionNumberGeneratorInterface
{
    public function generate(Department $department): string;

    public function suggestNextNumber(Department $department): string;
}
