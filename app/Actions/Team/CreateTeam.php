<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Models\Department;
use App\Models\Team;

use function array_merge;

class CreateTeam
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(Department $department, array $data): Team
    {
        return Team::query()->create(array_merge(
            [
                'department_id' => $department->id,
            ],
            $data,
        ));
    }
}
