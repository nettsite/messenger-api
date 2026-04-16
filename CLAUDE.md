# CLAUDE.md

File guides Claude Code (claude.ai/code) when working in this repo.

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

**Laravel package** on [spatie/laravel-package-tools](https://github.com/spatie/laravel-package-tools). Push-based messaging from Laravel backend to mobile users.

### Package Bootstrap
`src/MessengerServiceProvider.php` extends Spatie's `PackageServiceProvider`. Handles config publishing, migration registration, view publishing, command registration via `configurePackage()`.

### Delivery Architecture
- **FCM (Firebase Cloud Messaging)** — Laravel calls FCM HTTP API directly; no Firestore or Cloud Functions needed
- FCM payload: `notification` block (auto-displayed by FCM SDK for background/terminated) + `android` block with `channel_id: messenger_messages`, `sound: default`, `priority: HIGH` — matches channel Flutter app creates on first launch
- **Polling fallback** — mobile app polls on open/resume for non-GMS devices (post-2019 Huawei, etc.) and FCM failures
- No WebSockets/Reverb needed

### User Model
- Uses host app's User model — set via `messenger.user_model` config (defaults to `App\Models\User`)
- `HasMessenger` trait adds `messengerEnrollment()`, `deviceTokens()`, `groups()`, `messageReceipts()`, `markMessageRead()` to host User model
- `MessengerAuthenticatable` contract must be implemented by host User model

### Database Tables
- `messenger_enrollments` — morph table tracking user messenger status (`active`, `pending`, `suspended`); created on first login/registration
- `messenger_device_tokens` — FCM tokens per user/device; used for push delivery
- `messenger_messages` — `body`, `url` (nullable), `sender_type`, `sender_id`, `scheduled_at`, `sent_at`
- `messenger_message_recipients` — `message_id`, `recipient_type` (user/group/all), `recipient_id` (nullable)
- `messenger_message_receipts` — `message_id`, `user_id`, `delivered_at`, `read_at`
- `messenger_groups` / `messenger_group_users`
- `messenger_replies` — `message_id`, `user_id`, `body`

### Messaging Rules
- Direction: **admin → user only**; users reply but cannot initiate
- Content: text body + optional URL
- Supports scheduled sending (`scheduled_at`)
- Broadcast targets: individual user, group, or all users

### Authentication (Mobile API)
Laravel Sanctum — email + password registration/login. Social sign-on planned later.

Registration controlled by `messenger.registration.mode` config (`MESSENGER_REGISTRATION_MODE` env):
- `open` — self-register, immediately active, Sanctum token returned
- `approval` — self-register, `status=pending`, no token until admin approves via Filament
- `closed` — no self-registration; admin creates users in Filament panel

### Filament Panel
Admin UI for group management, message composition (via `MessengerService`), message history with aggregate read stats (e.g. "47/120 read"). Integrated via `MessengerPlugin`. Uses **Filament 5.4.x** — action classes under `Filament\Actions\*`, not `Filament\Tables\Actions\*`. Always use `search-docs` or Context7 before writing Filament code.

### MariaDB UUID Foreign Keys
MariaDB 10.7+ treats `uuid` as native binary type distinct from `char(36)`. FK constraints require exact type match — `char(36)` column **cannot** reference `uuid` primary key; MariaDB throws `errno: 150 "Foreign key constraint is incorrectly formed"`.

**Rule:** any column with `->foreign()` pointing to messenger table `id` (always `uuid`) must use `$table->uuid(...)`, not `$table->char(..., 36)`.

Morph columns (`user_id`, `sender_id`, `author_id`, `recipient_id`) have **no FK constraint**, may stay as `char(36)` — they reference host app's User model (integer or UUID PKs).

Affected stubs (already fixed):
- `create_messenger_message_recipients` — `message_id`
- `create_messenger_message_receipts` — `message_id`
- `create_messenger_conversations` — `message_id`
- `update_messenger_conversation_messages` — `conversation_id`
- `create_messenger_group_users` — `group_id`

### Testing
- Pest 4 with Orchestra Testbench — no running Laravel app needed
- `tests/TestCase.php` registers service provider, configures `testing` database
- Migration stubs loaded alphabetically by `File::allFiles()` — name new stubs starting with `update_` (not `add_`) so they sort after `create_` stubs
- `tests/ArchTest.php` enforces no `dd`/`dump`/`ray` in source
- CI matrix: PHP 8.3–8.4 × Laravel 12–13 × prefer-lowest/stable