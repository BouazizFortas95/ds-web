<?php

return [
    'locales' => ['en', 'ar', 'fr'],
    'default' => env('APP_LOCALE', 'en'),
    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),

    'directions' => [
        'en' => 'ltr',
        'ar' => 'rtl',
        'fr' => 'ltr',
    ],

    'names' => [
        'en' => 'English',
        'ar' => 'العربية',
        'fr' => 'Français',
    ],
];
