# Changelog

All notable changes to `nettsite/messenger` will be documented in this file.

## [1.1.0] - 2026-04-05

### Breaking changes

- **Removed `MessengerUser` model.** The package no longer ships its own users table or authentication model. Your application's existing User model is used exclusively. Set `MESSENGER_USER_MODEL` in your `.env` (defaults to `App\Models\User`).
- **Removed `RegistrationMode::Approval`.** The `approval` registration mode depended on the now-removed `UserStatus` enum. Only `open` and `closed` are valid values for `MESSENGER_REGISTRATION_MODE`.
- **`MessengerPanelProvider` is no longer auto-registered.** The standalone panel is opt-in — add it manually to `bootstrap/providers.php` if needed. The recommended integration path is `MessengerPlugin` (see below).

### Added

- **`MessengerPlugin`** — integrates Messenger into your existing Filament panel with a single line:
  ```php
  ->plugin(MessengerPlugin::make())
  ```
  Adds Messages and Groups resources to whichever panel you choose.

- **`php artisan messenger:install`** — guided setup command that publishes config and migrations, optionally runs migrations, checks for the `HasMessenger` trait on your User model, and prints the plugin registration snippet.

### Migration guide

1. Add `HasMessenger`, `HasApiTokens`, and `implements MessengerAuthenticatable` to your User model.
2. Add `->plugin(MessengerPlugin::make())` to your Filament panel provider.
3. Remove `MESSENGER_REGISTRATION_MODE=approval` from your `.env` if set — use `open` or `closed`.
4. Remove any reference to `MessengerUser` or `UserStatus` in your application code.
5. Drop the `messenger_users` table if you ran migrations from a previous version:
   ```sql
   DROP TABLE IF EXISTS messenger_users;
   ```

---

## [1.0.2] - 2026-04-01

- Added server-configured polling interval for web clients via `GET /api/messenger/config`.

## [1.0.1] - 2026-03-30

- Added message view page, reply thread display, and admin respond flow in Filament.

## [1.0.0] - 2026-03-28

- Initial release.
- `MessengerUser` model with open / approval / closed registration modes.
- FCM push delivery with polling fallback.
- Filament admin panel for messages, groups, and user management.
- Sanctum-based mobile API (register, login, device tokens, messages, replies).
