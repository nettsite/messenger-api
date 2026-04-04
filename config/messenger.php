<?php

// config for NettSite/Messenger
return [
    'user_model' => null, // null = MessengerUser; 'App\Models\User' = host model

    'fcm' => [
        'credentials' => env('MESSENGER_FCM_CREDENTIALS', storage_path('app/firebase-credentials.json')),
        'project_id' => env('MESSENGER_FCM_PROJECT_ID'),
    ],

    'panel' => [
        'id' => 'messenger',
        'path' => 'messenger',
        'guard' => 'web', // set to 'messenger' to use MessengerUser as the panel auth model
    ],

    'registration' => [
        'mode' => env('MESSENGER_REGISTRATION_MODE', 'open'), // 'open' | 'approval' | 'closed'
    ],

    'polling' => [
        'interval' => (int) env('MESSENGER_POLL_INTERVAL', 30), // seconds; used by web clients
    ],
];
