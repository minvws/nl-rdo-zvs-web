<?php

declare(strict_types=1);

namespace App\Enums;

enum TimelineType: string
{
    case ASSIGNMENT_OCCURRENCE = 'assignment_occurrence';
    case CONTACT_ATTACHED = 'contact_attached';
    case CONTACT_PIVOT_UPDATED = 'contact_pivot_updated';
    case DEADLINE_ADJUSTMENT_OCCURRENCE = 'deadline_adjustment_occurrence';
    case NOTE = 'note';
    case STATUS_OCCURRENCE = 'status_occurrence';
    case TIMELINEABLE_CREATED = 'timelineable_created';
    case TERM_CREATED = 'term_created';
    case TERM_UPDATED = 'term_updated';
    case TERM_DELETED = 'term_deleted';
    case CONTACT_DETACHED = 'contact_detached';
    case REFERENCED_OCCURRENCE = 'referenced_occurrence';
    case POLICY_DEPARTMENT_CHANGED = 'policy_department_changed';
    case PETITION_CUSTOM_PROPERTIES_CHANGED = 'petition_custom_properties_changed';
    case PETITION_CUSTOM_DATES_CHANGED = 'petition_custom_dates_changed';
    case PETITION_UPDATED = 'petition_updated';
    case DECISION_UPDATED = 'decision_updated';
    case CUSTOM_COST_UPDATED = 'custom_cost_updated';
    case EXTERNAL_URL_UPDATED = 'external_url_updated';
    case QUERYSNAPSHOT_UPDATED = 'querysnapshot_updated';
    case CORRESPONDENCE_UPDATED = 'correspondence_updated';
    case DELIVERABLE_CREATED = 'deliverable_created';
    case DELIVERABLE_UPDATED = 'deliverable_updated';
    case DELIVERABLE_DELETED = 'deliverable_deleted';
    case PROCESSING_STEP_CREATED = 'processing_step_created';
    case PROCESSING_STEP_UPDATED = 'processing_step_updated';
    case PROCESSING_STEP_DELETED = 'processing_step_deleted';
    case DRAFT_TERM_CREATED = 'draft_term_created';
    case DRAFT_TERM_UPDATED = 'draft_term_updated';
    case DRAFT_TERM_DELETED = 'draft_term_deleted';
    case PETITION_ARCHIVED = 'petition_archived';
    case DECISION_ARCHIVED = 'decision_archived';
    case DECISION_UNARCHIVED = 'decision_unarchived';
}
