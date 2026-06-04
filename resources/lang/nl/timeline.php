<?php

declare(strict_types=1);

return [
    'note_added' => 'heeft een notitie toegevoegd op',

    'contact_attached' => [
        'body' => ':contact is toegevoegd als :role',
    ],
    'checked' => 'zijn aangevinkt',
    'none_checked' => 'Geen details aangevinkt',

    'assignment' => [
        'assigned_to' => 'heeft de zaak toegewezen aan :assignee',
        'dismissed' => 'heeft de behandelaar van de zaak gehaald',
        'secondary_assigned_to' => 'heeft :assignee als achtervang toegewezen',
        'secondary_dismissed' => 'heeft de achtervang van de zaak gehaald',
    ],
    'contact_detached' => [
        'body' => ':contact is losgekoppeld als :role',
    ],
    'contact_pivot_updated' => [
        'body' => 'Gegevens van :contact zijn gewijzigd',
    ],
    'policy_department_changed' => 'Beleidsdirecties zijn gewijzigd naar :policyDepartments',

    'filter_groups' => [
        'updates' => 'Aanpassingen',
        'attachments' => 'Koppelingen',
        'notes' => 'Notities',
        'status_changes' => 'Status wijzigingen',
        'term_adjustments' => 'Termijn aanpassingen',
        'assignments' => 'Toewijzingen',
        'event_changes' => 'Kalender aangepast',
    ],

    'filter_by_group' => 'Filter',
    'all_timeline_items' => '(Geen filter)',

    'fallback_message' => 'Activiteit kan niet meer gevonden worden (type: :type, id: :id): neem contact op met :contact.',
    'team_changed' => 'Team gewijzigd',

    'final_decision_set' => [
        'with_decision' => ':decision gemarkeerd als finaal besluit',
        'without_decision' => 'het finale besluit is verwijderd',
    ],
];
