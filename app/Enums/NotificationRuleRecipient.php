<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationRuleRecipient: string
{
    case PRIMARY_ASSIGNEE = 'primary_assignee';
}
