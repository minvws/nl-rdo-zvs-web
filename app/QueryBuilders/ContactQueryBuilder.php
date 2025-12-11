<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\Contact;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static ContactQueryBuilder query()
 *
 * @template-extends Builder<Contact>
 */
class ContactQueryBuilder extends Builder
{
    public function notArchived(): ContactQueryBuilder
    {
        return $this->whereNull('archived_at');
    }

    public function whereDepartment(Department $department): ContactQueryBuilder
    {
        return $this->where('department_id', $department->id);
    }
}
