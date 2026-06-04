<?php

declare(strict_types=1);

use App\Enums\TimelineFilterGroup;
use App\Enums\TimelineType;

return [
    'groups' => [
        TimelineFilterGroup::UPDATES->value => [
            TimelineType::CORRESPONDENCE_UPDATED->value,
            TimelineType::CUSTOM_COST_UPDATED->value,
            TimelineType::EXTERNAL_URL_UPDATED->value,
            TimelineType::PETITION_CUSTOM_DATES_CHANGED->value,
            TimelineType::PETITION_CUSTOM_PROPERTIES_CHANGED->value,
            TimelineType::PETITION_UPDATED->value,
            TimelineType::DECISION_UPDATED->value,
            TimelineType::POLICY_DEPARTMENT_CHANGED->value,
            TimelineType::QUERYSNAPSHOT_UPDATED->value,
            TimelineType::TIMELINEABLE_CREATED->value,
        ],

        TimelineFilterGroup::ATTACHMENTS->value => [
            TimelineType::REFERENCED_OCCURRENCE->value,
            TimelineType::CONTACT_ATTACHED->value,
            TimelineType::CONTACT_DETACHED->value,
            TimelineType::DELIVERABLE_CREATED->value,
            TimelineType::DELIVERABLE_DELETED->value,
            TimelineType::DELIVERABLE_UPDATED->value,
            TimelineType::DECISION_UPDATED->value,
            TimelineType::PROCESSING_STEP_CREATED->value,
            TimelineType::PROCESSING_STEP_DELETED->value,
            TimelineType::PROCESSING_STEP_UPDATED->value,
            TimelineType::DEADLINE_ADJUSTMENT_OCCURRENCE->value,
        ],

        TimelineFilterGroup::NOTES->value => [
            TimelineType::NOTE->value,
        ],

        TimelineFilterGroup::STATUS_CHANGES->value => [
            TimelineType::STATUS_OCCURRENCE->value,
        ],

        TimelineFilterGroup::TERM_ADJUSTMENTS->value => [
            TimelineType::DRAFT_TERM_CREATED->value,
            TimelineType::DRAFT_TERM_DELETED->value,
            TimelineType::DRAFT_TERM_UPDATED->value,
            TimelineType::TERM_CREATED->value,
            TimelineType::TERM_DELETED->value,
            TimelineType::TERM_UPDATED->value,
        ],

        TimelineFilterGroup::ASSIGNMENTS->value => [
            TimelineType::ASSIGNMENT_OCCURRENCE->value,
        ],
        TimelineFilterGroup::EVENTS->value => [
            TimelineType::PETITION_EVENTS_CREATED->value,
        ],
    ],
];
