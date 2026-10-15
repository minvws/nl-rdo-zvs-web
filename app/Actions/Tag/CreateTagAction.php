<?php

declare(strict_types=1);

namespace App\Actions\Tag;

use App\Models\Department;
use App\Models\Tag;

use function array_merge;

class CreateTagAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(Department $department, array $data): Tag
    {
        return Tag::query()->create(array_merge(
            $data,
            [
                'department_id' => $department->id,
            ],
        ));
    }
}
