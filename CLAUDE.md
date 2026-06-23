# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Laravel 13 app (PHP 8.3) with a separate admin panel and a public frontend. SQLite by default, Tailwind v4 + Vite for assets.

**Product spec, status & roadmap:** see [`docs/PRODUCT.md`](docs/PRODUCT.md) — what the product is, what's built vs. TODO, and the staged plan (chat MVP → gamification → monetization).

## Commands

```bash
composer setup          # one-time: install, .env, key:gen, migrate, npm install, build
composer dev            # run everything: php serve + queue:listen + pail (logs) + vite, concurrently
composer test           # clears config, then php artisan test
php artisan test --filter=SomeTest      # single test
vendor/bin/pint         # format / lint (Laravel Pint, run before committing)
php artisan migrate --seed              # DatabaseSeeder + AdminSeeder
```

`composer dev` is the normal way to run locally — don't start `artisan serve` alone, the queue and vite are needed too. Tests use an in-memory sqlite (`phpunit.xml`), no setup required.

## Architecture

**Two auth guards, two user tables.** `web` guard → `User` model; `admin` guard → `Admin` model (separate `admins` table). They are independent — admin code always uses `auth('admin')` / `Auth::guard('admin')`, never the default guard. Guards defined in `config/auth.php`.

**Admin routes are bootstrapped separately.** `routes/admin.php` is loaded by the `then:` callback in `bootstrap/app.php` (under the `web` middleware group), not by default route loading. All admin routes are prefixed `admin` / named `admin.*`. Protected routes use the `admin.auth` middleware alias → `AdminAuthMiddleware` (redirects guests to `admin.login`, 401s for ajax/json).

**Admin model** (`app/Models/Admin.php`) has its own constants for `ROLES`, `LOGIN_METHODS`, an `enabled` flag (blocked admins can't log in), and OAuth support — `password` is nullable so Google-only admins exist without a local password. Avatar logic (`profileImageUrl`, `initials`) lives on the model; external (`http`) avatars are passed through, local ones resolve to the public disk.

**Service layer holds business logic, HTTP-free.** Controllers validate input, call a service, map the result to a response. Services live in `app/Services/...` (e.g. `Admin\AuthService`) and return a **`ValidationService`** object rather than throwing — see `app/Services/ValidationService.php`. The pattern:

```php
$result = $this->authService->attemptLogin($email, $password, $remember);
if (!$result->isSuccessfulCheck()) {
    return back()->with('error', $result->getFirstError());
}
```

`ValidationService` carries success state, errors, an HTTP status, and a bag of validated items (`addValidatedItems` / `getValidatedItem`). Use it for any multi-step check whose outcome the controller must branch on. OAuth is handled with Laravel Socialite (`google`/`github`/`facebook`).

**Global helper functions.** `app/Helpers/resources.php` is autoloaded globally (both via `composer.json` `files` and `LoadHelperFilesProvider`). Available everywhere — call directly, no import:
- `loadFiles($path)` / `auto_version()` — cache-busted public asset URL (appends `?v=<mtime>`).
- `saveFileToStorage($file, $dir)` / `deleteFile($name, $dir)` — public-disk file storage; `deleteFile` ignores `http` URLs (external avatars).
- `fullLog($throwableOrString)` — error log with stack trace.

**User-facing strings are localized.** Controller flash messages and admin UI text come from `lang/en/admin/backend.php` (backend/messages) and `lang/en/admin/frontend.php` (UI labels). Convention: `__('admin/backend.auth.invalid-credentials')`. Add new strings here, don't hardcode.

**Flash + tab convention.** Controllers redirect with `->with('success', ...)` / `->with('error', ...)` (rendered by `<x-admin.flash>`). The profile page is tabbed — controllers `session()->flash('active_profile_tab', 'profile'|'password'|'support')` so the right tab reopens after a redirect.

**App-specific config in `config/platform.php`** — support inbox email and the allowed `support_topics` list (validated against in `ProfileController`). Put app-level settings here, not in framework config files.

**Views** are Blade-component based: `resources/views/components/admin/*` (admin layout/topbar/sidebar/ui) and `components/frontend/*` (frontend layout, theme-switch). Admin and frontend have distinct layouts.

## Conventions

- Controllers are thin: validate → service → response. Push branching logic into a service returning `ValidationService`.
- Reach for the global helpers and the localized `__()` strings before writing new utilities or inline text.
- Default seeded admin: `master@admin.com` (`AdminSeeder`). Factory-seeded one: `admin@example.com` / `password` (`DatabaseSeeder`).