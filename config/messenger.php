<?php

// config for NettSite/Messenger
return [
    'user_model' => env('MESSENGER_USER_MODEL', 'App\Models\User'),

    'fcm' => [
        // Path to Firebase service account JSON. Defaults to storage/app/private/fcm-credentials.json.
        // Can be absolute or relative to the application base path.
        // project_id is extracted from the credentials file automatically — no separate env needed.
        'credentials' => env('MESSENGER_FCM_CREDENTIALS', storage_path('app/private/fcm-credentials.json')),
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
