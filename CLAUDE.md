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
- `resourceRoutesCallback($controller, $param, except: [])` — the admin CRUD route block, as a callback for `Route::group()`. Every admin resource uses it; see `routes/admin.php`.

**The LLM seam.** All model access goes through `App\Contracts\LlmClient` — three verbs, because the descent only ever needs three things: `streamTeachingTurn()` (streamed prose), `gradeCheckpoint()` (a **strict JSON-Schema structured output**, never parsed from prose), and `summarizeContext()`. `CHAT_PROVIDER` binds `GeminiClient` or `AnthropicClient` in `AppServiceProvider`. Schemas live in one place: `App\Services\Llm\GradingSchema`. Tests bind `Tests\Support\FakeLlmClient` — no test touches the network.

**The descent.** `DescentService` owns depth and checkpoint state and knows nothing about HTTP; `ChatStreamingService` owns the SSE wire; `MasteryService` owns the concept map and only ever moves a concept's state on **graded evidence**. A failed grading call must never cost a learner their layer.

`DescentService::open()` is the entry point for the composer: a URL in the prompt is fetched by `SourceFetcher` and stored as the subject's `Source`, and `DescentPrompt::sourceNote()` grounds every teaching turn in it. `SourceFetcher` is the app's only outbound fetch — **it resolves and range-checks every host, including each redirect hop, before a socket opens.** Never loosen that: the URL comes from a visitor and the fetch runs inside the network.

**SSE events are `stage` / `token` / `done` / `error`.** The `stage` frames are *real* — each is emitted at the moment that work happens and carries the numbers behind it, so the thinking panel is a log, not a spinner. Never emit one on a timer or for work that didn't occur.

**Subject, not hole.** The model stays `Conversation` (that's what it is at the data layer); everything a learner sees says **subject**. Routes are `subjects.*` (the library: `/subjects`, `/subjects/organization`) and `subject.*` (one subject: show / stream / checkpoint / share / file). Progression words are fixed: **Layer NN**, *Descend* / *Go deeper*, *Prove it*, "Depth reached: Layer X of Y", "N-day descent", "Your deepest dive".

**The app shell.** `<x-frontend.layout shell>` wraps a page in the persistent subject rail (`components/frontend/sidebar`); its data comes from `AppServiceProvider::composeSidebar()`, not from controllers. Which view the rail shows travels in the **unencrypted `dth_subject_view` cookie** (excepted in `bootstrap/app.php`) so the folder tree is only queried when it is on screen. `SubjectLibraryService` cursor-paginates the library; `subjects.index` returns just the rows partial when asked for `fragment=1`, which is how search and infinite scroll reuse one renderer. `SubjectFolderService` guards the two tree invariants: no folder inside its own subtree, and the depth cap from `platform.subjects.max_folder_depth`.

**Drag-and-drop is an enhancement, never an implementation.** Every mutation on the organiser is a real form post to a real route; `subjects.js` fills in forms the server rendered rather than inventing endpoints, so the whole surface works with JavaScript off.

**User-facing strings are localized.** Controller flash messages and admin UI text come from `lang/en/admin/backend.php` (backend/messages) and `lang/en/admin/frontend.php` (UI labels). Convention: `__('admin/backend.auth.invalid-credentials')`. Add new strings here, don't hardcode.

**Two languages, two layers.** The *interface* language is negotiated per request by `SetLocale` (session → one-year cookie → `Accept-Language`) from the registry in `config/platform.php` → `locales`; `admin*` is excepted and stays English. Adding a language is one registry entry plus a `lang/{code}` directory — `<x-frontend.lang-switch>` and the RO validation `attributes` follow automatically. Admin-managed copy (learning modes) resolves through `LearningMode::label()`: only non-default locales carry `frontend.modes.*` keys, so English keeps following the database.

The *teaching* language is separate and lives on the subject (`conversations.locale`, set from the locale at `open()`). `DescentPrompt::languageNote()` re-sends `[LANGUAGE] …` every turn — in the message list, never the frozen system prompt — because a model drifts back to the language of its instructions after a few layers, and `GradingRequest::$language` carries the same to the verdict. The guide is told to follow the learner over the directive if they write in something else, and that `**Checkpoint:**` never translates: `DescentService::currentCheckpoint()` and `markdown.js` both match that literal string. `tests/Feature/LocaleTest.php` asserts key parity and placeholder parity across locales — a missing key silently renders the English string, so it can't be left to review.

**Flash + tab convention.** Controllers redirect with `->with('success', ...)` / `->with('error', ...)` (rendered by `<x-admin.flash>`). The profile page is tabbed — controllers `session()->flash('active_profile_tab', 'profile'|'password'|'support')` so the right tab reopens after a redirect.

**App-specific config in `config/platform.php`** — support inbox email and the allowed `support_topics` list (validated against in `ProfileController`), plus `locales` (the language registry), `chat`, `mastery`, `subjects` (page sizes, folder depth cap) and `sources` (fetch timeout, byte cap, redirect cap, words that reach the prompt). Put app-level settings here, not in framework config files.

**Views** are Blade-component based: `resources/views/components/admin/*` (admin layout/topbar/sidebar/ui) and `components/frontend/*` (frontend layout, sidebar, navbar, footer, depth-rail, subject-link, subject-status, mode-picker, markdown…). Admin and frontend have distinct layouts. Row and tree markup lives in Blade partials (`frontend/subjects/partials/*`) that are reused verbatim by the fragment endpoints — if you find yourself writing markup in JS, the partial is the answer.

**Motion tokens, not magic numbers.** `resources/css/app.css` defines `--motion-instant|feedback|state|panel|milestone|ambient` and four easings, named for the job. Use those (`duration-(--motion-state)`) rather than arbitrary ms values. The rule the frontend is built on: *motion must explain state, direction, hierarchy, progress or causality* — if an animation answers none of those, it doesn't ship. The learning session is deliberately quieter than the landing page; ambient movement suspends under `body.is-studying`.

## Conventions

- Controllers are thin: validate → service → response. Push branching logic into a service returning `ValidationService`.
- Reach for the global helpers and the localized `__()` strings before writing new utilities or inline text. `tests/Feature/LangKeyTest.php` fails the build if a raw key reaches a page.
- Never convey state by colour alone — every status token also carries an icon and a label.
- Default seeded admin: `master@admin.com` (`AdminSeeder`). Factory-seeded one: `admin@example.com` / `password` (`DatabaseSeeder`). `LearningModeSeeder` is idempotent and seeds the six teaching modes.