<?php

declare(strict_types=1);

namespace App\Enums;

enum RouteName: string
{
    // admin
    case ADMIN_SHOW = 'admin.show';

    // admin.policy-department
    case ADMIN_POLICY_DEPARTMENT_CREATE = 'admin.policy-department.create';
    case ADMIN_POLICY_DEPARTMENT_EDIT = 'admin.policy-department.edit';
    case ADMIN_POLICY_DEPARTMENT_INDEX = 'admin.policy-department.index';
    case ADMIN_POLICY_DEPARTMENT_STORE = 'admin.policy-department.store';
    case ADMIN_POLICY_DEPARTMENT_UPDATE = 'admin.policy-department.update';

    // admin.public-holiday
    case ADMIN_PUBLIC_HOLIDAY_CREATE = 'admin.public-holiday.create';
    case ADMIN_PUBLIC_HOLIDAY_DELETE = 'admin.public-holiday.delete';
    case ADMIN_PUBLIC_HOLIDAY_EDIT = 'admin.public-holiday.edit';
    case ADMIN_PUBLIC_HOLIDAY_INDEX = 'admin.public-holiday.index';
    case ADMIN_PUBLIC_HOLIDAY_STORE = 'admin.public-holiday.store';
    case ADMIN_PUBLIC_HOLIDAY_UPDATE = 'admin.public-holiday.update';

    // admin.user
    case ADMIN_USER_CREATE = 'admin.user.create';
    case ADMIN_USER_EDIT = 'admin.user.edit';
    case ADMIN_USER_INDEX = 'admin.user.index';
    case ADMIN_USER_OTP_RESET = 'admin.user.otp-reset';
    case ADMIN_USER_UPDATE = 'admin.user.update';
    case ADMIN_USER_STORE = 'admin.user.store';

    // departments
    case DEPARTMENTS_SHOW = 'departments.show';

    // departments.admin.petition_types
    case DEPARTMENTS_ADMIN_PETITION_TYPES_CREATE = 'departments.admin.petition-types.create';
    case DEPARTMENTS_ADMIN_PETITION_TYPES_EDIT = 'departments.admin.petition-types.edit';
    case DEPARTMENTS_ADMIN_PETITION_TYPES_INDEX = 'departments.admin.petition-types.index';

    // departments.admin.petition_categories
    case DEPARTMENTS_ADMIN_PETITION_CATEGORIES_CREATE = 'departments.admin.petition-categories.create';
    case DEPARTMENTS_ADMIN_PETITION_CATEGORIES_EDIT = 'departments.admin.petition-categories.edit';
    case DEPARTMENTS_ADMIN_PETITION_CATEGORIES_DELETE = 'departments.admin.petition-categories.delete';
    case DEPARTMENTS_ADMIN_PETITION_CATEGORIES_INDEX = 'departments.admin.petition-categories.index';

    // departments.contacts
    case DEPARTMENTS_CONTACTS_INDEX = 'departments.contacts.index';
    case DEPARTMENTS_CONTACTS_INDEX_FILTER = 'departments.contacts.index_filter';
    case DEPARTMENTS_CONTACTS_CREATE = 'departments.contacts.create';
    case DEPARTMENTS_CONTACTS_EDIT = 'departments.contacts.edit';
    case DEPARTMENTS_CONTACTS_SHOW = 'departments.contacts.show';
    case DEPARTMENTS_CONTACTS_ARCHIVE_STORE = 'departments.contacts.archive.store';

    // departments.petitions
    case DEPARTMENTS_PETITIONS_INDEX = 'departments.petitions.index';
    case DEPARTMENTS_PETITIONS_INDEX_FILTER = 'departments.petitions.index_filter';
    case DEPARTMENTS_PETITIONS_SHOW = 'departments.petitions.show';
    case DEPARTMENTS_PETITIONS_STORE = 'departments.petitions.store';
    case DEPARTMENTS_PETITIONS_CREATE = 'departments.petitions.create';
    case DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT = 'departments.petitions.change-status.edit';
    case DEPARTMENTS_PETITIONS_CHANGE_STATUS_UPDATE = 'departments.petitions.change-status.update';
    case DEPARTMENTS_PETITIONS_CREATE_REFRESH_DEADLINE = 'departments.petitions.create.refresh-deadline';

    // departments.petitions.properties
    case DEPARTMENTS_PETITIONS_PROPERTIES_SHOW = 'departments.petitions.properties.show';
    case DEPARTMENTS_PETITIONS_PROPERTIES_EDIT = 'departments.petitions.properties.edit';
    case DEPARTMENTS_PETITIONS_PROPERTIES_UPDATE = 'departments.petitions.properties.update';

    // departments.petitions.assign-user
    case DEPARTMENTS_PETITIONS_ASSIGN_USER_SHOW = 'departments.petitions.assign-user.show';
    case DEPARTMENTS_PETITIONS_ASSIGN_USER_EDIT = 'departments.petitions.assign-user.edit';
    case DEPARTMENTS_PETITIONS_ASSIGN_USER_UPDATE = 'departments.petitions.assign-user.update';

    // departments.petitions.contacts
    case DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM = 'departments.petitions.contacts.attach_form';
    case DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM_FILTER = 'departments.petitions.contacts.attach_form.filter';
    case DEPARTMENTS_PETITIONS_CONTACTS_ATTACH = 'departments.petitions.contacts.attach';
    case DEPARTMENTS_PETITIONS_CONTACTS_DETACH = 'departments.petitions.contacts.detach';
    case DEPARTMENTS_PETITIONS_CONTACTS_UPDATE_PIVOT = 'departments.petitions.contacts.update_pivot';

    // departments.petitions.custom_petition_property
    case DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_SHOW = 'departments.petitions.custom_petition_property.show';
    case DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_EDIT = 'departments.petitions.custom_petition_property.edit';
    case DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_UPDATE = 'departments.petitions.custom_petition_property.update';

    // departments.petitions.archive
    case DEPARTMENTS_PETITIONS_ARCHIVE_STORE = 'departments.petitions.archive.store';

    // departments.petitions.petition-events
    case PETITION_EVENTS_WIZARD_RESET = 'petition-events.wizard.reset';
    case PETITION_EVENTS_WIZARD_STEP = 'petition-events.wizard.step';
    case PETITION_EVENTS_WIZARD_SELECT_TYPE = 'petition-events.wizard.select-type';
    case PETITION_EVENTS_WIZARD_CREATE = 'petition-events.wizard.create';
    case PETITION_EVENTS_WIZARD_SUBMIT_FORM = 'petition-events.wizard.submit-form';
    case PETITION_EVENTS_WIZARD_DELETE_LAST = 'petition-events.wizard.delete-last';
    case PETITION_EVENTS_WIZARD_STORE = 'petition-events.wizard.store';

    // departments.decisions.archive
    case DEPARTMENTS_DECISIONS_ARCHIVE_STORE = 'departments.decisions.archive.store';

    // departments.decisions.unarchive
    case DEPARTMENTS_DECISIONS_UNARCHIVE_STORE = 'departments.decisions.unarchive.store';

    // departments.petitions.custom-dates
    case DEPARTMENTS_PETITIONS_CUSTOM_DATES_EDIT = 'departments.petitions.custom-dates.edit';
    case DEPARTMENTS_PETITIONS_CUSTOM_DATES_SHOW = 'departments.petitions.custom-dates.show';
    case DEPARTMENTS_PETITIONS_CUSTOM_DATES_UPDATE = 'departments.petitions.custom-dates.update';

    // departments.petitions.custom-costs
    case DEPARTMENTS_PETITIONS_CUSTOM_COSTS_EDIT = 'departments.petitions.custom-costs.edit';
    case DEPARTMENTS_PETITIONS_CUSTOM_COSTS_SHOW = 'departments.petitions.custom-costs.show';
    case DEPARTMENTS_PETITIONS_CUSTOM_COSTS_UPDATE = 'departments.petitions.custom-costs.update';

    // departments.petitions.external-urls
    case DEPARTMENTS_PETITIONS_EXTERNAL_URLS_EDIT = 'departments.petitions.external-urls.edit';
    case DEPARTMENTS_PETITIONS_EXTERNAL_URLS_SHOW = 'departments.petitions.external-urls.show';
    case DEPARTMENTS_PETITIONS_EXTERNAL_URLS_UPDATE = 'departments.petitions.external-urls.update';

    // departments.petitions.querysnapshotss
    case DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_EDIT = 'departments.petitions.querysnapshots.edit';
    case DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_SHOW = 'departments.petitions.querysnapshots.show';
    case DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_UPDATE = 'departments.petitions.querysnapshots.update';

    // departments.petitions.petition_deliverables
    case DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_DELETE = 'departments.petitions.petition_deliverable.delete';
    case DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_CREATE = 'departments.petitions.petition_deliverable.create';
    case DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_EDIT = 'departments.petitions.petition_deliverable.edit';
    case DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_STORE = 'departments.petitions.petition_deliverable.store';
    case DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_UPDATE = 'departments.petitions.petition_deliverable.update';

    // departments.timelineable.notes
    case DEPARTMENTS_TIMELINEABLE_NOTES_CREATE = 'departments.timelineable.notes.create';
    case DEPARTMENTS_TIMELINEABLE_NOTES_STORE = 'departments.timelineable.notes.store';

    // departments.petitions.policy-department
    case DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_EDIT = 'departments.petitions.policy-department.edit';
    case DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_SHOW = 'departments.petitions.policy-department.show';
    case DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_UPDATE = 'departments.petitions.policy-department.update';

    // departments.petitions.exports
    case DEPARTMENTS_PETITIONS_EXPORTS_EXPORT = 'departments.petitions.exports.export';
    case DEPARTMENTS_PETITIONS_EXPORTS_INDEX = 'departments.petitions.exports.index';
    case DEPARTMENTS_PETITIONS_EXPORTS_DOWNLOAD = 'departments.petitions.exports.download';
    case DEPARTMENTS_PETITIONS_EXPORTS_DELETE = 'departments.petitions.exports.delete';

    // departments.petitions.terms
    case DEPARTMENTS_PETITIONS_TERMS_DELETE = 'departments.petitions.terms.delete';
    case DEPARTMENTS_PETITIONS_TERMS_CREATE = 'departments.petitions.terms.create';
    case DEPARTMENTS_PETITIONS_TERMS_EDIT = 'departments.petitions.terms.edit';
    case DEPARTMENTS_PETITIONS_TERMS_STORE = 'departments.petitions.terms.store';
    case DEPARTMENTS_PETITIONS_TERMS_UPDATE = 'departments.petitions.terms.update';

    // departments.petitions.draft-term
    case DEPARTMENTS_PETITIONS_DRAFT_TERM_CREATE = 'departments.petitions.draft-term.create';
    case DEPARTMENTS_PETITIONS_DRAFT_TERM_STORE = 'departments.petitions.draft-term.store';
    case DEPARTMENTS_PETITIONS_DRAFT_TERM_EDIT = 'departments.petitions.draft-term.edit';
    case DEPARTMENTS_PETITIONS_DRAFT_TERM_UPDATE = 'departments.petitions.draft-term.update';
    case DEPARTMENTS_PETITIONS_DRAFT_TERM_DELETE = 'departments.petitions.draft-term.delete';

    // departments.decisions
    case DEPARTMENTS_DECISIONS_INDEX = 'departments.decisions.index';
    case DEPARTMENTS_DECISIONS_INDEX_FILTER = 'departments.decisions.index_filter';
    case DEPARTMENTS_DECISIONS_CREATE = 'departments.decisions.create';
    case DEPARTMENTS_DECISIONS_STORE = 'departments.decisions.store';
    case DEPARTMENTS_DECISIONS_SHOW = 'departments.decisions.show';
    case DEPARTMENTS_DECISIONS_EDIT = 'departments.decisions.edit';
    case DEPARTMENTS_DECISIONS_UPDATE = 'departments.decisions.update';
    case DEPARTMENTS_DECISIONS_PROPERTIES = 'departments.decisions.properties';

    // departments.petitions.decisions
    case DEPARTMENTS_PETITIONS_DECISIONS_CREATE = 'departments.petitions.decisions.create';
    case DEPARTMENTS_PETITIONS_DECISIONS_STORE = 'departments.petitions.decisions.store';
    case DEPARTMENTS_PETITIONS_DECISION_ATTACH_FORM = 'departments.petitions.decisions.attach-form';
    case DEPARTMENTS_PETITIONS_DECISION_ATTACH = 'departments.petitions.decisions.attach';
    case DEPARTMENTS_PETITIONS_DECISION_DETACH = 'departments.petitions.decisions.detach';

    // departments.decision.petition.attach
    case DEPARTMENTS_DECISION_PETITION_ATTACH_FORM = 'departments.decision.petitions.attach-form';
    case DEPARTMENTS_DECISION_PETITION_ATTACH = 'departments.decisions.petitions.attach';
    case DEPARTMENTS_DECISION_PETITION_DETACH = 'departments.decisions.petitions.detach';

    // departments.decisions.processing-steps
    case DEPARTMENTS_DECISIONS_PROCESSING_STEPS_INDEX = 'departments.decisions.processing-steps.index';
    case DEPARTMENTS_DECISIONS_PROCESSING_STEPS_CREATE = 'departments.decisions.processing-steps.create';
    case DEPARTMENTS_DECISIONS_PROCESSING_STEPS_STORE = 'departments.decisions.processing-steps.store';
    case DEPARTMENTS_DECISIONS_PROCESSING_STEPS_EDIT = 'departments.decisions.processing-steps.edit';
    case DEPARTMENTS_DECISIONS_PROCESSING_STEPS_UPDATE = 'departments.decisions.processing-steps.update';
    case DEPARTMENTS_DECISIONS_PROCESSING_STEPS_DELETE = 'departments.decisions.processing-steps.delete';

    // departments.petitions.petition.attach
    case DEPARTMENTS_PETITION_PETITION_ATTACH_FORM = 'departments.petitions.petitions.attach-form';
    case DEPARTMENTS_PETITION_PETITION_ATTACH = 'departments.petitions.petitions.attach';
    case DEPARTMENTS_PETITION_PETITION_DETACH = 'departments.petitions.petitions.detach';

    // departments.petitions.correspondence
    case DEPARTMENTS_PETITIONS_CORRESPONDENCE_INDEX = 'departments.petitions.correspondence.index';
    case DEPARTMENTS_PETITIONS_CORRESPONDENCE_SHOW = 'departments.petitions.correspondence.show';
    case DEPARTMENTS_PETITIONS_CORRESPONDENCE_EDIT = 'departments.petitions.correspondence.edit';
    case DEPARTMENTS_PETITIONS_CORRESPONDENCE_UPDATE = 'departments.petitions.correspondence.update';
    case DEPARTMENTS_PETITIONS_CORRESPONDENCE_WORD_TEMPLATE_DOWNLOAD = 'departments.petitions.correspondence.word_template_download';

    // forgot_password
    case FORGOT_PASSWORD_EMAIL = 'forgot_password.email';
    case FORGOT_PASSWORD_REQUEST = 'forgot_password.request';

    // login
    case LOGIN = 'login';
    case LOGIN_ATTEMPT = 'login_attempt';

    // password
    case PASSWORD_UPDATE = 'password.update';

    // confirm
    case CONFIRM = 'confirm';

    // notifications
    case NOTIFICATIONS_INDEX = 'notifications.index';
    case NOTIFICATIONS_SHOW = 'notifications.show';
    case NOTIFICATIONS_MARK_ALL_READ = 'notifications.markAllRead';
    case NOTIFICATIONS_MARK_AS_READ = 'notifications.markAsRead';
    case NOTIFICATIONS_MARK_AS_UNREAD = 'notifications.markAsUnread';
}
