# nettsite/messenger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nettsite/messenger.svg?style=flat-square)](https://packagist.org/packages/nettsite/messenger)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/nettsite/messenger/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nettsite/messenger/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nettsite/messenger.svg?style=flat-square)](https://packagist.org/packages/nettsite/messenger)

A Laravel package for push-based messaging from your backend to mobile and web app users. Provides a Filament admin UI for composing and tracking messages, a REST API for mobile clients, and Firebase Cloud Messaging (FCM) delivery with a polling fallback.

## Requirements

- PHP 8.4+
- Laravel 12 or 13
- Filament 5.x (for the admin panel integration)
- Laravel Sanctum 4.x

## Installation

Install via Composer:

```bash
composer require nettsite/messenger
```

Run the installer:

```bash
php artisan messenger:install
```

This publishes the config file, publishes and runs the migrations, and prints the next steps.

### Prepare your User model

Your application's User model must implement `MessengerAuthenticatable` and use the `HasMessenger` trait and `HasApiTokens`:

```php
use Laravel\Sanctum\HasApiTokens;
use NettSite\Messenger\Contracts\MessengerAuthenticatable;
use NettSite\Messenger\Traits\HasMessenger;

class User extends Authenticatable implements MessengerAuthenticatable
{
    use HasApiTokens, HasMessenger;
}
```

### Register the Filament plugin

Add `MessengerPlugin` to your Filament panel provider:

```php
use NettSite\Messenger\Filament\MessengerPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(MessengerPlugin::make());
}
```

This adds **Messages** and **Groups** to your existing admin panel. No separate panel or login page is created.

## Configuration

```php
// config/messenger.php

return [
    // Your application's User model
    'user_model' => env('MESSENGER_USER_MODEL', 'App\Models\User'),

    // Firebase Cloud Messaging credentials
    'fcm' => [
        'credentials' => env('MESSENGER_FCM_CREDENTIALS', storage_path('app/firebase-credentials.json')),
        'project_id'  => env('MESSENGER_FCM_PROJECT_ID'),
    ],

    // 'open'   — API register endpoint creates users
    // 'closed' — registration via API is disabled (403)
    'registration' => [
        'mode' => env('MESSENGER_REGISTRATION_MODE', 'open'),
    ],

    // Polling interval in seconds, sent to web clients via GET /api/messenger/config
    'polling' => [
        'interval' => env('MESSENGER_POLL_INTERVAL', 30),
    ],
];
```

## Mobile / Web API

All endpoints are prefixed `/api/messenger/`. Authentication uses Sanctum bearer tokens.

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `config` | — | Returns polling interval |
| POST | `auth/register` | — | Register a new user (open mode only) |
| POST | `auth/login` | — | Login → Sanctum token |
| DELETE | `auth/logout` | Bearer | Revoke current token |
| POST | `auth/device` | Bearer | Update FCM device token |
| GET | `messages` | Bearer | Paginated sent messages for the user |
| GET | `messages/poll` | Bearer | Undelivered messages; stamps `delivered_at` |
| POST | `messages/{id}/read` | Bearer | Mark a message read |
| GET | `messages/{id}/replies` | Bearer | List replies on a message |
| POST | `messages/{id}/replies` | Bearer | Submit a reply |

## Sending messages

Use the `Messenger` facade from your application code or a scheduled command:

```php
use NettSite\Messenger\Facades\Messenger;

// Send to all users immediately
Messenger::broadcast('Your order has shipped!', url: 'https://example.com/orders/123');

// Send to a specific user
Messenger::sendToUser($user, 'Your item is ready for collection.');

// Send to a group
Messenger::sendToGroup($group, 'Maintenance window tonight at 22:00.');

// Schedule for later
Messenger::broadcast('Happy New Year!', scheduledAt: now()->startOfYear()->addYear());
```

Scheduled messages are dispatched by the `messenger:send-scheduled` command. Add it to your scheduler:

```php
// bootstrap/app.php
Schedule::command('messenger:send-scheduled')->everyMinute();
```

## FCM setup

1. In the Firebase Console, generate a service account JSON key for your project.
2. Place the file at `storage/app/firebase-credentials.json` (or set `MESSENGER_FCM_CREDENTIALS`).
3. Set `MESSENGER_FCM_PROJECT_ID` to your Firebase project ID.

For devices without Google Mobile Services (e.g. post-2019 Huawei), the polling endpoint (`GET messages/poll`) serves as the delivery fallback.

## Standalone panel (optional)

If your application does not have an existing Filament panel, you can use the bundled standalone panel instead of `MessengerPlugin`. Add `MessengerPanelProvider` manually to `bootstrap/providers.php`:

```php
NettSite\Messenger\Filament\MessengerPanelProvider::class,
```

The standalone panel is configured via the `panel` key in `config/messenger.php`.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
