<?php

declare(strict_types=1);

namespace App\Actions\NotificationRule;

use App\Models\Department;
use App\Models\NotificationRule;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\NotificationRuleData;

use function array_merge;

class CreateNotificationRuleAction
{
    public function execute(Department $department, NotificationRuleData $data): NotificationRule
    {
        return NotificationRule::query()->create(array_merge(
            $data->toAttributes(),
            [
                'department_id' => $department->id,
                'active_from' => CalendarDate::today(),
            ],
        ));
    }
}
