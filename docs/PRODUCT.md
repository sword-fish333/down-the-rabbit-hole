# Down the Rabbit Hole — Product, Architecture & Roadmap

> Living source of truth for **what the product is**, **what's built**, and **what's next**.
> Pairs with `CLAUDE.md` (engineering conventions). Update the status table (§7) with every shipped slice.

---

## 1. What this is

An AI-chat **deep-learning** app, not a chat app. You **name a subject, fall in, and at each layer the
AI makes you prove you understood before the next, deeper layer unlocks — until you surface an expert.**

The spine is the *descent*: a single conversation per rabbit hole with a measurable **depth**. Every
reward is anchored to **depth of learning**, never time-on-app. Design language is Blade-Runner-noir ×
Alice-in-Wonderland; the principles are **deep work, flow, and mastery** (clear goals → immediate
feedback → challenge/skill balance).

**The one product loop:** `ask → AI teaches one layer → "prove it" checkpoint → pass → descend deeper`.

---

## 2. Current state (implemented)

- **Stack:** Laravel 13, PHP 8.3, SQLite (default), Tailwind v4 + Vite. Frontend is **Blade + vanilla
  JS** (not Inertia/React) — already server-rendered, so public pages are crawlable natively.
- **Auth:** two guards — `web` → `User`, `admin` → `Admin` (separate tables). **Admin** is fully wired
  (credential + Google OAuth, profile CRUD, support inbox, dashboard). **`web`** is now wired too —
  learner register / login / logout via `FrontEnd\AuthService`, with guest holes claimed on sign-up/login.
- **Frontend:** the noir × Alice design system (`resources/css/app.css` `@theme` OKLCH tokens,
  dark-default, Material Symbols, Space Grotesk / DM Sans / JetBrains Mono); landing page
  (`resources/views/frontend/home/index.blade.php`) with an **ungated composer** whose form posts to a
  placeholder (`action="#"`, awaiting `POST /descend`); navbar, footer, premium dark/light toggle
  (vanilla JS, `localStorage['dth-theme']`, `.dark` on `<html>`); bespoke atmosphere in
  `public/css/frontend/custom.css`.
- **Service layer:** thin controllers → services returning a `ValidationService` result object
  (`app/Services/ValidationService.php`); example `app/Services/Admin/AuthService.php`.
- **Helpers (global, autoloaded):** `loadFiles()` / `auto_version()` (cache-busted asset URLs),
  `saveFileToStorage()` / `deleteFile()`, `fullLog()`.
- **Localization:** all UI strings via `__('frontend.*')` / `__('admin/...')`.
- **Models:** `User`, `Admin` only. `users` table already carries `first_name, last_name, name, email,
  password, enabled, profile_image, phone, salutation, apple_id, login_method`.
- **Packages:** `laravel/framework ^13.8`, `sanctum ^4`, `socialite ^5.28`, `tinker`.

**Built — Stage 0 descent engine:** the rabbit-hole chat — `conversations` / `messages`, Anthropic
streaming over SSE behind an `LlmClient` seam, the depth + "prove-it" checkpoint state machine
(`DescentService`), model routing + daily caps, and the event-driven XP/streak foundation
(`xp_events` ledger, `streaks`, `LayerCompleted` → `AwardLayerRewards`). Guest-capable (session
ownership). Covered by `tests/Feature/DescentTest.php`.

**Still absent:** achievements / leagues / quests, subscriptions / payments, public sharing, the
resource/book library. **Stage 0 is complete** — the descent loop, learner auth, and the
event-driven gamification foundation are all in.

---

## 3. Conventions (see `CLAUDE.md` for the full set)

- Controllers are **thin**: validate → call a service → map the result. Branching logic lives in
  services that return `ValidationService` (`isSuccessfulCheck()` / `getFirstError()` / `addValidatedItems()`).
- Reach for the **global helpers** and **`__()` strings** before writing new utilities or inline text.
- App-level settings go in `config/platform.php`.
- Run `vendor/bin/pint` before committing.

---

## 4. Product strategy (extracted, trimmed to this Blade stack)

### Gamification — reward *depth*, not time
- **XP** for asking meaningful follow-ups, reaching new depth, completing a layer, and contributions
  that get upvoted (later). Diminishing returns per action/day to kill grinding.
- **Streak — "The White Rabbit":** unit = **one completed layer (or path step) per day** — a
  *learning-equivalent*, mirroring Duolingo's "one lesson, not XP" insight. Freezes/repair later.
- **Build event-driven from day one** (an append-only XP ledger + listeners). Retrofitting XP onto a
  live system is painful; the ledger is cheap now.
- **Defer:** leagues, quests, badges, reputation, levels — all bolt onto the same event stream later.
- **Healthy, not exploitative:** reward learning outcomes, offer graceful exits ("you've learned a lot
  today"), ship a **Focus Mode** (Forest-style), and **never pay-to-win on knowledge** (money buys
  cosmetics / more AI / convenience, never exclusive learning or reputation).

### Monetization — protect margins against real LLM cost
- Launch **freemium + a single ~$20 "Wonderland" Pro tier** (don't over-engineer tiers).
- **Meter the expensive path:** daily AI-message credits for free users, **model routing**
  (Haiku → Sonnet → Opus by turn complexity), **prompt caching** of the system prompt, hard spend caps.
- Later: $8–10 mid-tier, **Bookshop.org affiliate** (industry-leading ~10%) on referenced books,
  **BYOK** for power users. **No Stripe / Cashier in the MVP** — gate with a simple daily counter.

### Growth — the public rabbit-hole loop
- **Blade is already SSR**, so **public shareable hole pages need no Inertia SSR** — a real
  simplification vs. the source spec (which assumed Inertia + React). Add per-page OG/meta + a sitemap
  and the pages are a UGC-distribution + SEO loop out of the box.
- Later: Dropbox-style **double-sided referral** (bonus credits both sides), topic communities.

### Stack-divergence note
The source spec assumes **Laravel 12 + Inertia + React**; reality is **Blade + vanilla JS**. Adapt
accordingly: **SSE consumed by a `fetch`-stream reader** (not an EventSource/React client), SEO is
native, and the "contextual transform bar" is Blade components + small JS, not React.

---

## 5. `book-to-skill` — where it fits

`book-to-skill` (https://github.com/virgiliojr94/book-to-skill) is a **Python CLI** that compiles a
technical book into a structured, token-cheap **Claude skill bundle**: a front-loaded `SKILL.md`
(mental models + chapter index) plus on-demand `chapters/`, `glossary.md`, `patterns.md`, and
`cheatsheet.md` — "density over completeness."

**Role for us:** the **content pipeline** for the future Resource/Book library (Stage 2). Ingest a book
→ store the bundle → **inject it as cited, low-token grounding** into the descent chat, and use it to
**scaffold a Learning Path**. Treat it as an **external batch tool** (a queued job) whose markdown
output we persist and feed as context — **do not reimplement it**. The only seam needed today is the
`LlmClient` interface (a future ingestion worker reuses the same client) + a future `Resource` model.

---

## 6. X article (`x.com/heynavtoor/article/...`)

**Could not retrieve** — X long-form articles are behind auth/paywall (HTTP 402). The pasted product
spec already covers the deep-work / flow / mastery methodology comprehensively, so this is **not
blocking**. To fold its specific framework in, **paste the key points here** and we'll reconcile.

---

## 7. Roadmap & status (certain → speculative)

Legend: ✅ done · 🟡 in progress · ⬜ todo.

### Stage 0 — MVP: the descent loop
| Status | Item |
|---|---|
| ✅ | **`conversations` + `messages`** — `current_depth`, per-layer `status` (exploring / checkpoint_pending / surfaced) |
| ✅ | **Thin `ChatController`** — `descend` / `continue` / `show` / `stream`; landing form wired → `POST /descend` |
| ✅ | **`DescentService`** — depth/checkpoint state machine, trailing-JSON control-block parsing (fail-safe), model routing |
| ✅ | **`ChatStreamingService`** — SSE `StreamedResponse` + Claude stream + post-stream persist (tokens, message), persists on disconnect |
| ✅ | **`LlmClient` interface → `GeminiClient` / `AnthropicClient`** — single swap seam; `CHAT_PROVIDER` picks, bound in `AppServiceProvider` |
| ✅ | **"Prove-it-to-descend" prompt** — one frozen, prompt-cached system prompt (`DescentPrompt`); teach → checkpoint → self-grade pass/retry via trailing JSON control block (no quiz engine) |
| ✅ | **Gamification foundation** — append-only `xp_events`, `streaks`; `LayerCompleted` → auto-discovered queued `AwardLayerRewards` (`XpService` + `StreakService`) |
| ✅ | **Cost control** — daily turn counter (cache-keyed, no column), Haiku for grade/guest, model routing by depth |
| ✅ | **Ungated first descent** — anonymous guest hole, tracked in the session |
| ✅ | **Web-guard auth** — register / login / logout (`FrontEnd\AuthService` + `ValidationService`); claims guest holes on sign-up/login; **activates XP/streak** (guests earn nothing until they claim) |
| ⬜ | **Front-end polish** — render assistant Markdown (currently plain `pre-wrap`), conversation list / resume |

### Stage 1 — engagement & first revenue
⬜ Public shareable hole pages (Blade + OG/meta + sitemap) · ⬜ Leagues / Quests / Badges (new listeners
on the same events) · ⬜ Cosmetic store (streak repairs, themes) · ⬜ Double-sided referral · ⬜ $8–10 mid-tier.

### Stage 2 — UGC & content
⬜ Resource/Book library + voting + reputation-gated curation · ⬜ Bookshop.org affiliate · ⬜
`book-to-skill` ingestion pipeline · ⬜ Learning Paths (shareable) · ⬜ MCP book-search tool in chat.

### Stage 3 — scale & creators
⬜ BYOK · ⬜ Creator monetization (path/curator revenue share) · ⬜ Topic communities ("Warrens") · ⬜
Stripe / Cashier (when the paid tier launches).

---

## 8. LLM specifics (grounded via the `claude-api` skill)

- **Current provider (MVP): Google Gemini** — `CHAT_PROVIDER=gemini`, one key (`GEMINI_API_KEY`, free
  from [AI Studio](https://aistudio.google.com/apikey)). Free tier covers the routed models, so the
  MVP costs nothing. `App\Services\Llm\GeminiClient` hits
  `v1beta/models/{model}:streamGenerateContent?alt=sse` — system prompt in `systemInstruction`,
  assistant role renamed to `model`, `thought` parts dropped, implicit caching (no `cache_control`).
- **Models & pricing** (per 1M tokens, in / out, paid tier): `gemini-3.5-flash-lite` **$0.30 / $2.50**
  (cheap + mid), `gemini-3.5-flash` **$1.50 / $9** (deep). Both free-of-charge on the free tier.
- **Alternate provider:** `CHAT_PROVIDER=anthropic` → `AnthropicClient` (needs `ANTHROPIC_API_KEY`).
  SDK path if adopted: official **`anthropic-ai/sdk`** (Composer). PHP top-level args
  are **camelCase** (`maxTokens`), nested keys copied verbatim from docs.
- **Models & pricing** (per 1M tokens, in / out): `claude-opus-4-8` **$5 / $25**,
  `claude-sonnet-4-6` **$3 / $15**, `claude-haiku-4-5` **$1 / $5**. Route by turn complexity:
  Haiku (free/simple) → Sonnet (normal) → Opus (deep layers).
- **Streaming:** Laravel `StreamedResponse` with `Content-Type: text/event-stream`,
  `Cache-Control: no-cache`, **`X-Accel-Buffering: no`**, `flush()` after each chunk; the Blade page
  consumes it with a vanilla-JS **`fetch` reader** (POST to advance state, GET to stream).
- **Prompt caching:** keep the system prompt **frozen** (no interpolated subject/date) and mark it
  `cache_control: {type: "ephemeral"}`; verify `usage.cache_read_input_tokens > 0` on turn 2.
- **Thinking:** short teach/grade turns run fine with thinking off (or a "final answer only"
  instruction); reserve adaptive thinking for genuinely deep reasoning.
- Keep all Anthropic calls behind the `LlmClient` interface so the SDK stays swappable.

---

## 9. The signature mechanic — "prove it to descend" (design intent)

One conversation = one rabbit hole. `DescentService` drives a tiny state machine:

1. **Teach turn** — system prompt teaches exactly one layer for the current depth, then poses **one
   concrete checkpoint** (explain-back / apply-to-a-new-case / predict — not trivia). The model emits a
   trailing ```json control block (`{"phase":"checkpoint", ...}`); state → `checkpoint_pending`.
2. **Grade turn** — the user's answer is the proof. The model grades it and emits
   `{"phase":"grade","verdict":"pass|retry", ...}`. On **pass**, `current_depth++`, fire
   `LayerCompleted` (→ XP + streak), and the next teach turn goes deeper. On **retry**, stay.

Grading lives entirely in the model via instruction — **KISS, no rubric engine**. Missing/malformed
control block → safe default (`retry` / stay) + `fullLog`. The whole thing is event-driven so every
future mechanic (leagues, quests, badges) is just another listener.
