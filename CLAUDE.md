# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Testing
composer test              # run full test suite (vendor/bin/pest)
composer test-coverage     # run tests with code coverage report
./vendor/bin/pest --filter "test name"  # run a single test

# Code style
composer format            # auto-fix with Pint
composer analyse           # PHPStan static analysis (level 5)

# Package discovery (run after composer install)
composer prepare           # testbench package:discover
```

## Architecture Overview

This is a **Laravel package** built on [spatie/laravel-package-tools](https://github.com/spatie/laravel-package-tools). It provides push-based messaging from a Laravel backend to mobile app users.

### Package Bootstrap
`src/MessengerServiceProvider.php` extends Spatie's `PackageServiceProvider`, which handles config publishing, migration registration, view publishing, and command registration declaratively via `configurePackage()`.

### Delivery Architecture
- **FCM (Firebase Cloud Messaging)** — Laravel calls the FCM HTTP API directly; no Firestore or Cloud Functions needed
- FCM payload uses a `notification` block (auto-displayed by FCM SDK for background/terminated state) plus an `android` block specifying `channel_id: messenger_messages`, `sound: default`, and `priority: HIGH` — matching the channel created by the Flutter app on first launch
- **Polling fallback** — mobile app polls on open/resume for non-GMS devices (post-2019 Huawei, etc.) and FCM failures
- No WebSockets/Reverb required

### User Model
- Uses the host app's User model exclusively — set via `messenger.user_model` config (defaults to `App\Models\User`)
- `HasMessenger` trait adds `messengerEnrollment()`, `deviceTokens()`, `groups()`, `messageReceipts()`, and `markMessageRead()` to the host User model
- `MessengerAuthenticatable` contract must be implemented by the host User model

### Database Tables
- `messenger_enrollments` — morph table tracking each user's messenger status (`active`, `pending`, `suspended`); created on first login or registration
- `messenger_device_tokens` — FCM tokens per user/device; used for push delivery
- `messenger_messages` — `body`, `url` (nullable), `sender_type`, `sender_id`, `scheduled_at`, `sent_at`
- `messenger_message_recipients` — `message_id`, `recipient_type` (user/group/all), `recipient_id` (nullable)
- `messenger_message_receipts` — `message_id`, `user_id`, `delivered_at`, `read_at`
- `messenger_groups` / `messenger_group_users`
- `messenger_replies` — `message_id`, `user_id`, `body`

### Messaging Rules
- Direction is **admin → user only**; users can reply but cannot initiate
- Content: text body + optional URL
- Supports scheduled sending (`scheduled_at`)
- Broadcast targets: individual user, group, or all users

### Authentication (Mobile API)
Laravel Sanctum — email + password registration/login. Social sign-on planned for a later phase.

Registration is controlled by `messenger.registration.mode` config (`MESSENGER_REGISTRATION_MODE` env):
- `open` — self-register, immediately active, Sanctum token returned
- `approval` — self-register, `status=pending`, no token until admin approves via Filament
- `closed` — no self-registration; admin creates users in the Filament panel

### Filament Panel
Admin UI for group management, message composition (via `MessengerService`), and message history with aggregate read stats (e.g. "47/120 read"). Integrated into the host app's panel via `MessengerPlugin`. Uses **Filament 5.4.x** — action classes live under `Filament\Actions\*`, not `Filament\Tables\Actions\*`. Always use `search-docs` or Context7 before writing Filament code.

### Testing
- Pest 4 with Orchestra Testbench — no running Laravel app needed
- `tests/TestCase.php` registers the service provider and configures the `testing` database
- Migration stubs are loaded alphabetically by `File::allFiles()` — name new stubs starting with `update_` (not `add_`) so they sort after `create_` stubs
- `tests/ArchTest.php` enforces no `dd`/`dump`/`ray` in source
- CI matrix: PHP 8.3–8.4 × Laravel 12–13 × prefer-lowest/stable
