<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API Table Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines which tables can be exposed via the generic
    | API endpoints and which fields are allowed to be shown for each table.
    |
    */

    'tables' => [
        'users' => [
            'table' => 'users',
            'fields' => [
                'id',
                'name',
                'email',
                // 'email_verified_at',
                // 'password',
                // 'otp_confirmed_at',
                // 'otp_recovery_codes',
                // 'otp_secret',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'petitions' => [
            'table' => 'petitions',
            'fields' => [
                'id',
                'department_id',
                'description',
                'date_of_entry',
                'deadline_at',
                'number',
                'name',
                'message',
                'date_of_message',
                'petition_category_id',
                'petition_type_id',
                'petition_status_id',
                'department_id',
                'date_appealed_decision',
                'decision_date',
                'decision_reference',
                'archived_at',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'petition_petition' => [
            'table' => 'petition_petition',
            'fields' => [
                'petition_id',
                'related_petition_id',
            ],
            'filterable_fields' => [],
        ],

        'petition_deliverables' => [
            'table' => 'petition_deliverables',
            'fields' => [
                'id',
                'petition_id',
                'type',
                'deadline_at',
                'description',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'petition_draft_terms' => [
            'table' => 'petition_draft_terms',
            'fields' => [
                'id',
                'petition_id',
                'description',
                'start_date',
                'event_date',
                'days_after_event',
                'date_withdrawal',
                'days_after_date_withdrawal',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'petition_custom_properties' => [
            'table' => 'custom_petition_property_petition',
            'fields' => [
                'custom_petition_property_id',
                'petition_id',
            ],
            'filterable_fields' => [
                'custom_petition_property_id',
                'petition_id',
            ],
        ],

        'decision_petition' => [
            'table' => 'decision_petition',
            'fields' => [
                'decision_id',
                'petition_id',
                'is_final',
            ],
            'filterable_fields' => [],
        ],

        'categories' => [
            'table' => 'petition_categories',
            'fields' => [
                'id',
                'name',
                'department_id',
                'active',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'contacts' => [
            'table' => 'contacts',
            'fields' => [
                'id',
                'initials',
                'last_name',
                'organisation_name',
                // disabled due to privacy concerns
                // --------------------------------
                // 'email_address',
                // 'phone_number',
                // 'street',
                // 'house_number',
                // 'postal_code',
                // 'city',
                // 'type',
                // 'visiting_address_street',
                // 'visiting_address_house_number',
                // 'visiting_address_postal_code',
                // 'visiting_address_city',
                // 'postal_address_street',
                // 'postal_address_house_number',
                // 'postal_address_postal_code',
                // 'postal_address_city',
                // 'email_address_2',
                // 'email_address_3',
                // 'notes',
                // 'archived_at',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'terms' => [
            'table' => 'petition_terms',
            'fields' => [
                'id',
                'petition_id',
                'type',
                'start_date',
                'end_date',
                'duration_in_days',
                'penalty_amount_in_euros',
                'parent_id',
                'description',
                'legal_term_applied',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'decisions' => [
            'table' => 'decisions',
            'fields' => [
                'id',
                'name',
                'date',
                'type',
                'department_id',
                'reference',
                'reviewbatch',
                'archived_at',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'departments' => [
            'table' => 'departments',
            'fields' => [
                'id',
                'name',
                'slug',
                'abbreviation',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'petition_statuses' => [
            'table' => 'petition_statuses',
            'fields' => [
                'id',
                'status',
                'order',
                'status_group',
                'bg_color',
                'petition_type_id',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'petition_status_histories' => [
            'table' => 'petition_statuses_history_entries',
            'fields' => [
                'petition_id',
                'petition_status_id',
                'date',
                'created_at',
            ],
            'filterable_fields' => [
                'created_at',
            ],
        ],

        'petition_types' => [
            'table' => 'petition_types',
            'fields' => [
                'id',
                'name',
                'type',
                'department_id',
                'particularity_label',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'petition_custom_costs' => [
            'table' => 'custom_costs',
            'fields' => [
                'id',
                'petition_id',
                'custom_cost_type',
                'custom_cost_amount_in_cents',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],
        'petition_custom_dates' => [
            'table' => 'petition_custom_dates',
            'fields' => [
                'id',
                'petition_id',
                'date_label',
                'date',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'public_holidays' => [
            'table' => 'public_holidays',
            'fields' => [
                'id',
                'name',
                'date',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'processing_steps' => [
            'table' => 'processing_steps',
            'fields' => [
                'id',
                'name',
                'decision_id',
                'deadline_at',
                'status',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'policy_departments' => [
            'table' => 'policy_departments',
            'fields' => [
                'id',
                'name',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'custom_petition_properties_definitions' => [
            'table' => 'custom_petition_properties',
            'fields' => [
                'id',
                'petition_type_id',
                'name',
                'type',
                'ordering',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'petition_external_urls' => [
            'table' => 'petition_external_urls',
            'fields' => [
                'id',
                'petition_id',
                'petition_external_url_type',
                'url',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'petition_policy_department' => [
            'table' => 'petition_policy_department',
            'fields' => [
                'petition_id',
                'policy_department_id',
            ],
            'filterable_fields' => [],
        ],

        'petition_querysnapshots' => [
            'table' => 'petition_querysnapshots',
            'fields' => [
                'id',
                'petition_id',
                'querysnapshot_id',
                'querysnapshot_type',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'contact_petition' => [
            'table' => 'contact_petition',
            'fields' => [
                'id',
                'contact_id',
                'petition_id',
                'role',
                'reference',
                'correspondence_preference',
            ],
            'filterable_fields' => [],
        ],

        'custom_petition_properties' => [
            'table' => 'custom_petition_properties',
            'fields' => [
                'id',
                'petition_type_id',
                'name',
                'type',
                'ordering',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'petition_events' => [
            'table' => 'petition_events',
            'fields' => [
                'id',
                'petition_id',
                'type',
                'date',
                'duration',
                'penalties',
                'suspension_type',
                'result_type',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
            ],
        ],

        'petition_timeline_items' => [
            'table' => 'timeline_items',
            'fields' => [
                'internal_id',
                'timelineable_type',
                'timelineable_id',
                'user_id',
                'type',
                'data',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
                'timelineable_id',
            ],
        ],

        'petition_assignments' => [
            'table' => 'petition_assignments',
            'fields' => [
                'id',
                'petition_id',
                'user_id',
                'assignment_role',
                'created_at',
                'updated_at',
            ],
            'filterable_fields' => [
                'created_at',
                'updated_at',
                'petition_id',
                'user_id',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global API Settings
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],
];
