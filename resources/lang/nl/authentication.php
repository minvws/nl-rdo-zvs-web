<?php

declare(strict_types=1);

return [
    'login_failed' => 'Login gegevens zijn incorrect. Probeer het nog een keer.',
    'login' => 'Inloggen',
    'logout' => 'Uitloggen',
    'ratelimited' => 'Teveel inlogpogingen, probeer het opnieuw over :seconds seconden',
    'data' => 'Inloggegevens',

    'verify_email' => [
        'title' => 'Verifieer je e-mailadres',
        'verification_sent' => 'Er is een verificatie-mail verzonden',
        'resend' => 'Opnieuw verzenden',
    ],

    'forgot_password' => [
        'title' => 'Wachtwoord vergeten?',
    ],

    'reset_password' => [
        'title' => 'Wachtwoord resetten',
    ],

    'password-strength' => [
        'constraints' => '12 tot 128 tekens. Speciale tekens en spaties toegestaan.',
        'help' => 'Wachtwoord moet minimaal 12 tekens lang zijn.',
        'strength' => [
            'empty' => 'Leeg',
            'weak' => 'Zwak',
            'moderate' => 'Gemiddeld',
            'strong' => 'Sterk',
            'very-strong' => 'Zeer sterk',
        ],
    ],

    'one_time_password' => [
        'title' => 'Tweefactorauthenticatie',
        'code' => 'Tweefactorauthenticatie code',
    ],
];
