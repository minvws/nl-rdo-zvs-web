<?php

declare(strict_types=1);

return [
    'model_singular' => 'Gebruiker',
    'model_plural' => 'Gebruikers',

    'create' => 'Gebruiker toevoegen',
    'edit' => 'bewerken',
    'email' => 'E-mailadres',
    'name' => 'Naam',
    'password' => 'Wachtwoord',
    'remember_me' => 'Onthoud mij',
    'password_confirm' => 'Wachtwoord bevestigen',
    'no_users' => 'Er zijn nog geen gebruikers ingevoerd.',
    'password_reset_success' => 'Je wachtwoord is succesvol hersteld',
    'otp_reset' => 'Tweefactorauthenticatie herstellen',
    'otp_reset_success' => 'Tweefactorauthenticatie hersteld',
    'active' => 'Actief',

    'mail' => [
        'email_verification' => [
            'subject' => 'Verifieer email-adres',
            'text' => 'Klik op onderstaande button om dit email-adres te verifieren',
            'button_text' => 'Verifieer nu',
        ],
        'password_reset' => [
            'subject' => 'Herstel wachtwoord',
            'text' => 'Klik op onderstaande button om je wachtwoord opnieuw in te stellen',
            'button_text' => 'Herstel wachtwoord',
        ],
    ],

    'validation' => [
        'current_password_incorrect' => 'Je huidige wachtwoord is onjuist ingevuld',
        'current_password_required' => 'Vul je huidige wachtwoord in',
    ],
];
