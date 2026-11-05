<?php

declare(strict_types=1);

return [
    'note_added' => 'heeft een notitie toegevoegd op',
    'note_deleted' => [
        'title' => 'Notitie verwijderd',
        'files' => 'Verwijderde bestanden',
        'reason' => 'Toelichting',
    ],
    'delete_note' => [
        'title' => 'Notitie verwijderen',
        'description' => 'De notitie en alle bijbehorende bestanden worden verwijderd. Dit kan niet ongedaan worden gemaakt. Het verwijderen wordt vastgelegd in de tijdlijn.',
        'reason' => 'Toelichting (optioneel)',
        'reason_hint' => 'Noteer hier de reden van het verwijderen.',
    ],

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
    'contact_first_phone_contact' => [
        'body' => 'Eerste telefonisch contact met :contact was op :date',
    ],
    'policy_department_changed' => 'Beleidsdirecties zijn gewijzigd naar :policyDepartments',
    'tags_changed' => 'Tags zijn gewijzigd naar :tags',
    'tags_cleared' => 'Alle tags zijn van de zaak gehaald',

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
