<?php

declare(strict_types=1);

namespace App\Models\Builder;

use App\Enums\TermType;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<DepartmentTermTypeSetting>
 */
class DepartmentTermTypeSettingBuilder extends Builder
{
    public function whereDepartmentAndType(Department $department, TermType $termType): self
    {
        $this->query
            ->where(['department_id' => $department->id])
            ->where('term_type', $termType);

        return $this;
    }
}
