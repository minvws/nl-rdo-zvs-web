<?php

declare(strict_types=1);

namespace App\Actions\PolicyDepartment;

use App\Models\PolicyDepartment;

class UpdatePolicyDepartmentAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(PolicyDepartment $policyDepartment, array $data): void
    {
        $policyDepartment->update($data);
    }
}
