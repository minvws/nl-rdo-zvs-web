<?php

declare(strict_types=1);

namespace App\Actions\PolicyDepartment;

use App\Models\PolicyDepartment;

class CreatePolicyDepartmentAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): PolicyDepartment
    {
        // Set default active value if not provided
        if (!isset($data['active'])) {
            $data['active'] = true;
        }

        return PolicyDepartment::query()->create($data);
    }
}
