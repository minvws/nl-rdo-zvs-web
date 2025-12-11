<?php

declare(strict_types=1);

namespace App\Actions\PetitionCategory;

use App\Models\Department;
use App\Models\PetitionCategory;

use function array_merge;

class CreatePetitionCategory
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(Department $department, array $data): PetitionCategory
    {
        return PetitionCategory::query()->create(array_merge(
            [

                'department_id' => $department->id,
            ],
            $data,
        ));
    }
}
