<?php

// config for NettSite/Messenger
return [
    'user_model' => env('MESSENGER_USER_MODEL', 'App\Models\User'),

    'fcm' => [
        'credentials' => env('MESSENGER_FCM_CREDENTIALS', storage_path('app/firebase-credentials.json')),
        'project_id' => env('MESSENGER_FCM_PROJECT_ID'),
    ],

    // Only used by MessengerPanelProvider (standalone mode).
    // MessengerPlugin (recommended) does not use these settings.
    'panel' => [
        'id' => 'messenger',
        'path' => 'messenger',
        'guard' => 'web',
    ],

    'registration' => [
        'mode' => env('MESSENGER_REGISTRATION_MODE', 'open'), // 'open' | 'closed'
    ],

    'polling' => [
        'interval' => (int) env('MESSENGER_POLL_INTERVAL', 30), // seconds; used by web clients
    ],
];
