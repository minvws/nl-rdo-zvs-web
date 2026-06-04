<?php

declare(strict_types=1);

namespace App\Enums;

enum PetitionCriteria: string
{
    case APPLICANT = 'applicant';
    case ARCHIVE = 'archive';
    case ASSIGNED_USER = 'assigned_user';
    case CATEGORY = 'category';
    case CUSTOM_PROPERTY = 'custom_property';
    case DEADLINE_AT = 'deadline_at';
    case DEADLINE_DECISION_PERIOD = 'deadline_decision_period';
    case DEADLINE_NOTICE_OF_DEFAULT = 'deadline_notice_of_default';
    case DEADLINE_APPEAL_NOT_TIMELY = 'deadline_appeal_not_timely';
    case NAME = 'name';
    case NUMBER = 'number';
    case PENALTY_TO_DATE = 'penalty_to_date';
    case PETITION_TYPE = 'petition_type';
    case POLICY_DEPARTMENT = 'policy_department';
    case SEARCH = 'search';
    case STATUS_GROUP = 'status_group';
    case STATUS = 'status';
    case SUM_OF_PENALTIES_PER_DATE = 'sum_of_penalties_per_date';
    case TEAM = 'team';
}
