# Down the Rabbit Hole — Product, Architecture & Roadmap

> Living source of truth for **what the product is**, **what's built**, and **what's next**.
> Pairs with `CLAUDE.md` (engineering conventions). Update the status table (§8) with every shipped slice.

---

## 1. What this is

An AI-chat **deep-learning** app, not a chat app. You **name a subject, fall in, and at each layer the
AI makes you prove you understood before the next, deeper layer unlocks — until you surface an expert.**

The spine is the *descent*: a single conversation per subject with a measurable **depth**. Every
reward is anchored to **depth of learning**, never time-on-app. Design language is Blade-Runner-noir ×
Alice-in-Wonderland; the principles are **deep work, flow, and mastery** (clear goals → immediate
feedback → challenge/skill balance).

**The one product loop:** `ask → AI teaches one layer → "prove it" checkpoint → pass → descend deeper`.

---

## 2. Current state (implemented)

- **Stack:** Laravel 13, PHP 8.3, SQLite (default), Tailwind v4 + Vite. Frontend is **Blade + vanilla
  JS** (not Inertia/React) — already server-rendered, so public pages are crawlable natively.
- **Auth:** two guards — `web` → `User`, `admin` → `Admin` (separate tables). Both fully wired.
  Learners register with **first/last name**, get a **queued verification email**
  (`SendEmailVerificationEmail` → `VerifyEmailMail`, signed + expiring link hashed against the address
  on file), and can manage their own **profile** (details / password / avatar / learning record).
  Verification is a **nudge, not a gate** — an unverified learner still descends.
- **Frontend:** the noir × Alice design system (`resources/css/app.css` `@theme` OKLCH tokens plus a
  **motion-token layer**, dark-default, Space Grotesk / DM Sans / JetBrains Mono); landing page, subject
  library, learning workspace, auth, profile — all rendered from `resources/views/frontend/*` with
  shared Blade components. Bespoke atmosphere and the signature interactions live in
  `public/css/frontend/custom.css`.
- **Service layer:** thin controllers → services returning a `ValidationService` result object.
- **Helpers (global, autoloaded):** `loadFiles()` / `auto_version()`, `saveFileToStorage()` /
  `deleteFile()`, `fullLog()`, and **`resourceRoutesCallback()`** (the admin CRUD route block).
- **Localization:** every user-facing string via `__('frontend.*')` / `__('admin/...')`. English only
  today, but nothing is hardcoded — adding a locale is a lang-file drop.
- **Models:** `User`, `Admin`, `Conversation`, `Message`, `LearningMode`, `Concept`,
  `CheckpointAttempt`, `XpEvent`, `Streak`, `SubjectFolder`, `Source`.
- **Packages:** `laravel/framework ^13.8`, `sanctum ^4`, `socialite ^5.28`, `tinker`. **No new runtime
  dependency was added** for any of the above — see §5.

**Built — the descent engine.** `conversations` / `messages`, SSE streaming behind a normalized
`LlmClient` seam, the depth + checkpoint state machine (`DescentService`), **schema-enforced grading**,
the **mastery map**, **learning modes**, model routing + daily caps, and the event-driven XP/streak
foundation (`xp_events` ledger, `streaks`, `LayerCompleted` → `AwardLayerRewards`). Guest-capable
(session ownership). Covered by 82 tests, all offline (`Tests\Support\FakeLlmClient`, `Http::fake`).

**Built — the library.** The persistent **subject rail** on every app screen (recents or folder tree,
switched by an unencrypted `dth_subject_view` cookie so the tree is only queried when shown), the
**library** at `/subjects` (cursor pagination, escaped LIKE search, state filters, bulk delete, one
Blade partial serving both first paint and the `fragment=1` lazy-load), and the **organiser** at
`/subjects/organization` — an Obsidian-style tree with drag-and-drop filing, nested folders, rename,
and a client-side filter that reveals matches inside collapsed folders. Every mutation is a real form
post; drag-and-drop only fills in forms the server rendered, so the whole surface works without JS.

**Built — read-only sharing.** `share_token` on a conversation *is* the capability; `/s/{token}` is a
server-rendered public page and unsharing nulls the token, which kills the link. This is the SEO / UGC
loop in §6 with no second rendering path.

**Built — source-grounded learning (first pass, §7).** A URL in the composer is fetched by
`SourceFetcher` (SSRF-checked per host *and* per redirect hop, timeout + byte + redirect caps, regex
readability extraction, no new dependency), stored as a `Source`, and re-sent in every teaching
directive so it survives context compaction. The guide is told to teach from it and to say plainly
where it steps beyond the page. Chunking, locators and per-passage citations are still to come.

**Built — honest progress.** SSE now carries `stage` frames alongside `token`: each is emitted when
that work actually happens and carries its numbers ("Reading X — 1,200 words", "Recalling 4 concepts
you've proven", "Composing Layer 04"). The workspace renders them as a collapsible descent, and prose
is revealed a completed block at a time — committed blocks land in stable DOM once and animate once;
only the unfinished tail re-renders per frame.

**Still absent:** source chunking / citations (§7), achievements / leagues / quests, subscriptions /
payments, flashcards and spaced review across subjects.

**Vocabulary is fixed.** The model stays `Conversation`; the product says **subject** everywhere.
Progression words: **Layer NN**, *Descend* / *Go deeper*, *Prove it*, "Depth reached: Layer X of Y",
"N-day descent" (never "streak" in the UI), "Your deepest dive: [subject] — N layers".

---

## 3. Conventions (see `CLAUDE.md` for the full set)

- Controllers are **thin**: validate → call a service → map the result. Branching logic lives in
  services that return `ValidationService`.
- Reach for the **global helpers** and **`__()` strings** before writing new utilities or inline text.
- Admin CRUD routes use `resourceRoutesCallback(Controller::class, 'param', except: [...])`.
- App-level settings go in `config/platform.php`.
- Run `vendor/bin/pint` before committing.

---

## 4. The signature mechanic — "prove it to descend"

One conversation = one subject. `DescentService` drives a small state machine, and the important
design decision is that **its two turns use two different transports**:

1. **Teach turn** — `LlmClient::streamTeachingTurn()`. Streamed Markdown, rendered as it arrives. The
   system prompt teaches exactly one layer for the current depth, then poses **one concrete checkpoint**
   (explain-back / apply-to-a-new-case / predict — never trivia), ending on a `**Checkpoint:**` line.
   State → `checkpoint_pending`. The learner can ask for the *same* layer from a different angle
   ("explain differently", "give an analogy", "show the evidence", "challenge me") without advancing.
2. **Grade turn** — `LlmClient::gradeCheckpoint()`. A **separate, non-streaming, JSON-Schema-constrained**
   call returning a `GradingResult`. On a pass, `current_depth++`, fire `LayerCompleted` (→ XP + streak),
   and the mastery map updates. On a retry, the learner keeps their depth and their progress.

### Why the trailing control block is gone

The previous design ended every model turn with a fenced ```json control block that the app parsed to
learn the verdict. That was **fragile by construction**: a pedagogically perfect answer could still
carry malformed or missing JSON, and the failure mode was silent. All three major providers now enforce
JSON Schema server-side ([Gemini](https://ai.google.dev/gemini-api/docs/structured-output),
[Claude](https://platform.claude.com/docs/en/build-with-claude/structured-outputs),
[OpenAI](https://developers.openai.com/api/docs/guides/structured-outputs)), so the verdict is now
**well-formed by construction** instead of scraped out of prose.

`GradingResult::fromArray()` still clamps every field, and a provider that ignores the schema degrades
to a safe `retry` — but that is the belt to the schema's braces, not the mechanism. A grading call that
fails outright **never costs the learner their layer**.

```json
{
  "verdict": "pass",
  "score": 86,
  "confidence": 91,
  "criteria": [{ "name": "…", "met": true, "note": "…" }],
  "demonstrated_concepts": ["…"],
  "missing_concepts": ["…"],
  "misconceptions": [{ "concept": "…", "belief": "…", "correction": "…" }],
  "feedback": "…",
  "recommended_action": "descend"
}
```

### The provider seam

```php
interface LlmClient
{
    public function streamTeachingTurn(TeachingRequest $request): LlmStream;
    public function gradeCheckpoint(GradingRequest $request): GradingResult;
    public function summarizeContext(ContextSummaryRequest $request): ContextSummary;
}
```

`CHAT_PROVIDER` binds `GeminiClient` or `AnthropicClient` in `AppServiceProvider`. `LlmStream` is
iterated for text deltas and then read for `text()` / `usage()`, so a learner who disconnects mid-turn
still gets their partial layer persisted. `summarizeContext()` runs in a **queued job**
(`CompactConversationContext`) once a subject outgrows its verbatim window — a deep subject stays affordable
without ever putting a second round-trip on the critical path.

### Mastery map

`Concept` rows carry one of four states — **mastered**, **developing**, **misunderstood**,
**not explored** — and `MasteryService` only ever moves them on **graded evidence**. A concept becomes
mastered after N demonstrations (`platform.mastery.demonstrations_to_master`), and never regresses out
of mastery. A misconception is recorded with the belief that produced it, and the next teaching turn is
asked to weave a correction in — that is the spaced-review mechanic, driven by evidence rather than a
timer.

### Learning modes

Six seeded modes — **Socratic**, **Visual explanation**, **Exam preparation**, **Project-based**,
**Fast overview**, **Deep technical descent** — each carrying a `prompt_directive` appended to the
per-turn teaching instruction. Fully CRUD-managed from the admin panel (`admin/learning-mode`), so the
pedagogy is tunable without a deploy. `LearningModeSeeder` is idempotent (`updateOrCreate` by slug).

---

## 5. Frontend architecture

### Motion system

Durations and easings are **named for the job** in `resources/css/app.css`, not scattered as arbitrary
millisecond values:

| Token | Duration | Job |
|---|---|---|
| `--motion-instant` | 100ms | press / toggle |
| `--motion-feedback` | 160ms | hover + focus |
| `--motion-state` | 240ms | a component changed state |
| `--motion-panel` | 360ms | panel / learning-state transition |
| `--motion-milestone` | 640ms | one-shot: a layer unlocked |
| `--motion-ambient` | 18s | atmosphere, outside focused study only |

Easings: `--ease-snap` (precise UI), `--ease-out` (natural deceleration), `--ease-cinema` (emphasized),
`--ease-linear` (progress only).

**The governing rule:** motion must explain state, direction, hierarchy, progress or causality. Anything
that answers none of those is not in the codebase. The active learning session is deliberately
**quieter** than the landing page — ambient movement is suspended via `body.is-studying` the moment the
learner starts reading or writing.

### Signature interactions (three, reused everywhere)

1. **Lock-on** — a one-time cyan border trace when the topic field takes focus, then silence.
2. **Semantic light trail** — when a layer unlocks, a short line travels from the cleared layer to the
   newly opened one on the depth rail, showing causality, then removes itself.
3. **Focus aperture** — deep-work mode drops peripheral contrast and concentrates luminance on the
   reading column. Contrast only; the interface is never blurred.

### Accessibility & performance

- WCAG 2.2 AA target: skip link, one polite live region for all status announcements, real ARIA
  tablist/radiogroup/switch semantics, `:focus-visible` everywhere, keyboard parity with hover on every
  card, and **no state conveyed by colour alone** (every mastery and status token also carries an icon,
  a label, and — for `misunderstood` — a pattern).
- `prefers-reduced-motion` and `prefers-reduced-transparency` both have designed static fallbacks, not
  broken ones.
- The frontend layout dropped **jQuery, SweetAlert2 and imask** — nothing on the public side used them.
  Vite's font plugin was also removed (it fetched a face the design never uses).
- Markdown is rendered by a ~190-line XSS-safe renderer (`public/js/frontend/markdown.js`) for the live
  stream and `Str::markdown(html_input: escape)` for persisted turns — matching output, no dependency.
- Long transcripts use `content-visibility: auto`; streaming re-renders at most once per frame and never
  moves text the learner has already read.

---

## 6. Product strategy

### Gamification — reward *depth*, not time
- **XP** for completed layers, with the `xp_events` ledger as the source of truth. Diminishing returns
  per action/day to kill grinding.
- **Streak — "The White Rabbit":** unit = **one completed layer per day** — a *learning-equivalent*,
  mirroring Duolingo's "one lesson, not XP" insight.
- **Progressive disclosure:** during deep work the UI shows only objective, depth and progress; the
  learning record lives on the profile. The navbar shows a streak **only when it is alive** — no
  zero-state guilt, no loss-anxiety mechanics.
- **Defer:** leagues, quests, badges, reputation, levels — all bolt onto the same event stream later.
- **Never optimize for time on site.** The admin dashboard deliberately does not track messages sent or
  session length; both can rise while learning quality falls.

### Monetization — protect margins against real LLM cost
- Launch **freemium + a single ~$20 "Wonderland" Pro tier** (don't over-engineer tiers).
- **Meter the expensive path:** daily turn credits, **model routing** (cheap for grading, mid for
  shallow teaching, deep for the deepest layers), **prompt caching** of the frozen system prompt,
  **context compaction** on long subjects, hard spend caps.
- The paid tier should sell **depth and durability**, not access to knowledge: higher daily limits,
  source ingestion (§7), longer subjects, export, and the weekly mastery report. **Never pay-to-win on
  knowledge** — money buys convenience and more AI, never exclusive learning.
- **No Stripe / Cashier yet** — gate with the daily counter until the tier actually launches.

### Growth
- **Blade is already SSR**, so public shareable subject pages need no Inertia SSR — add per-page OG/meta
  and a sitemap and the pages are a UGC-distribution + SEO loop out of the box.
- Later: double-sided referral (bonus credits both sides), topic communities.

---

## 7. Source-grounded learning (specified, not yet built)

**The feature:** a learner drops a **URL or a document** into a subject; the guide reads it and teaches
*from it*, with checkpoints that quote it. This is the single highest-value addition to the product and
the most defensible thing to put behind the paid tier — it turns "explain this subject" into "make me
understand *this specific material*", which is what course notes, papers and books actually demand.

**Status: the URL half is built, deliberately without chunking.** Ingestion is a pipeline (fetch →
extract → chunk → store → retrieve → cite) and each stage has its own failure modes, so it was split:
today one page is fetched, extracted and re-sent whole (truncated to `platform.sources.prompt_words`)
in every teaching directive. That is enough for "make me understand *this article*" and it puts nothing
unreliable underneath grading — a checkpoint still grades the learner's understanding, not a retrieval
score. `sources` exists; `source_chunks` and `conversation_source` do not yet. Uploads are not built.

**What the built half guarantees.** `SourceFetcher` refuses any host that resolves into a private or
reserved range, re-checks on every redirect hop (Guzzle `on_redirect`), bounds the fetch by timeout,
bytes and hops, accepts only HTML/text, and refuses a page with under 100 readable words rather than
teaching from nothing. `SourceFetcherTest` asserts *no request is sent* for each refused case — that
assertion is the point of the test, not a detail of it.

### Data model

| Table | Purpose |
|---|---|
| `sources` | one ingested artefact: `type` (url / pdf / text), `url`, `title`, `author`, `published_at`, `status`, `checksum`, `bytes` |
| `source_chunks` | ordered extracts with a stable `locator` (page / heading / char range) — the anchor a citation points at |
| `conversation_source` | which sources ground which subject |

### Pipeline

1. `POST /subject/{id}/sources` accepts a URL or an upload; validates type, size and (for URLs) that the
   host resolves publicly — **SSRF is the first thing to get right here**.
2. A queued `IngestSource` job fetches and extracts. URLs need a readability pass; PDFs need a text
   layer (and OCR is out of scope — a scanned PDF should fail loudly, not silently produce nothing).
3. Chunks are stored with locators. Retrieval starts as **BM25/FTS over `source_chunks`** — good enough
   to ship, and it avoids an embedding provider on day one. Vector search is an optimisation, not a
   prerequisite.
4. `TeachingRequest` gains a `sources` array; the guide is instructed to teach from the extracts and to
   cite by locator. `GradingSchema` gains an optional `evidence` field so a checkpoint can require the
   learner to point at the passage.

### UI (already designed for)

The citation popover styling (`.dth-source-popover`) is in place — native Popover API + CSS anchor
positioning where supported, a centred fallback elsewhere, and a **bottom sheet on narrow screens**.
The "Show the evidence" contextual action already exists in the workspace and currently asks the guide
to name what the layer rests on; once sources exist it becomes a real citation surface.

**Ship it behind the paid tier**, metered by pages ingested rather than by subject.

---

## 8. Roadmap & status (certain → speculative)

Legend: ✅ done · 🟡 in progress · ⬜ todo.

### Stage 0 — MVP: the descent loop
| Status | Item |
|---|---|
| ✅ | **`conversations` + `messages`** — depth, per-layer status, running summary |
| ✅ | **Thin `ChatController`** — `index` / `descend` / `show` / `stream` / `checkpoint` |
| ✅ | **`DescentService`** — depth/checkpoint state machine, reframes, model routing |
| ✅ | **`ChatStreamingService`** — SSE `StreamedResponse`, persists on disconnect |
| ✅ | **Normalized `LlmClient`** — `streamTeachingTurn` / `gradeCheckpoint` / `summarizeContext`, `GeminiClient` + `AnthropicClient` |
| ✅ | **Schema-enforced grading** — trailing control block removed; `GradingSchema` + `GradingResult` |
| ✅ | **Mastery map** — `concepts` (mastered / developing / misunderstood / unexplored) from graded evidence; misconceptions resurface |
| ✅ | **`checkpoint_attempts`** — criterion-by-criterion feedback, confidence calibration |
| ✅ | **Learning modes** — 6 seeded, full admin CRUD, wired into the teaching directive |
| ✅ | **Context compaction** — queued `CompactConversationContext` keeps deep subjects affordable |
| ✅ | **Gamification foundation** — `xp_events`, `streaks`, `LayerCompleted` → `AwardLayerRewards` |
| ✅ | **Cost control** — daily turn counter, model routing, prompt caching, compaction |
| ✅ | **Ungated first descent** — anonymous guest subject, tracked in the session |
| ✅ | **Web-guard auth** — register / login / logout / Google, first+last name, queued email verification, learner profile |
| ✅ | **Frontend** — motion system, learning workspace, depth rail, mastery map, deep-work mode, Markdown rendering, subject library |
| ✅ | **Admin** — dashboard with learning-health metrics, learning-mode CRUD, learner management, subject oversight |

### Stage 1 — retention & utility
| Status | Item |
|---|---|
| ✅ | **Subject library** — `/subjects`, cursor pagination, search, state filters, bulk delete, lazy load |
| ✅ | **Organisation** — `subject_folders` tree, drag-and-drop filing, nested folders, filter |
| ✅ | **Persistent subject rail** — recents or folder tree on every app screen |
| ✅ | **Source-grounded learning, URL half (§7)** — SSRF-guarded fetch, extract, grounded teaching |
| 🟡 | **Source chunking + citations** — `source_chunks`, locators, FTS retrieval, `evidence` in the schema |
| ⬜ | Document/PDF upload · flashcards from the mastery map · notes & highlights · end-of-subject synthesis · export · cross-subject spaced review · weekly mastery report · learning paths |

### Stage 2 — engagement & first revenue
| Status | Item |
|---|---|
| ✅ | **Public shareable subject pages** — `share_token` capability, `/s/{token}`, server-rendered |
| ⬜ | Per-page OG images + sitemap for the shared pages |
| ⬜ | Achievements on the same event stream · double-sided referral · Stripe / Cashier at launch |

### Stage 3 — UGC & scale
⬜ Fork another learner's subject · ⬜ Curated expert learning paths · ⬜ Collaborative study rooms ·
⬜ Resource voting & reputation · ⬜ BYOK · ⬜ Creator monetization.

**Deliberately delayed:** leagues, cosmetic stores and creator monetization, until the metrics in §10
show learners complete layers and return for review.

---

## 9. LLM specifics

- **Current provider (MVP): Google Gemini** — `CHAT_PROVIDER=gemini`, one key (`GEMINI_API_KEY`, free
  from [AI Studio](https://aistudio.google.com/apikey)). `GeminiClient` hits
  `v1beta/models/{model}:streamGenerateContent?alt=sse` for teaching and `:generateContent` with
  `responseMimeType: application/json` + `responseJsonSchema` for grading. System prompt in
  `systemInstruction`, assistant role renamed to `model`, `thought` parts dropped, implicit caching.
- **Gemini models & pricing** (per 1M tokens, in / out, paid tier): `gemini-3.5-flash-lite`
  **$0.30 / $2.50** (cheap + mid), `gemini-3.5-flash` **$1.50 / $9** (deep). Both free on the free tier.
- **Alternate provider:** `CHAT_PROVIDER=anthropic` → `AnthropicClient` (needs `ANTHROPIC_API_KEY`).
  Structured outputs go in `output_config.format` (`{type: "json_schema", schema: …}`) — the older
  top-level `output_format` parameter is deprecated.
- **Claude models & pricing** (per 1M tokens, in / out):

  | Model | ID | Context | Input | Output |
  |---|---|---|---|---|
  | Claude Opus 5 | `claude-opus-5` | 1M | $5 | $25 |
  | Claude Sonnet 5 | `claude-sonnet-5` | 1M | $3 (intro $2 through 2026-08-31) | $15 (intro $10) |
  | Claude Haiku 4.5 | `claude-haiku-4-5` | 200K | $1 | $5 |

  Routed by depth + phase: **Haiku 4.5** for grading and summarising, **Sonnet 5** for shallow teaching,
  **Opus 5** past `CHAT_DEEP_THRESHOLD`. Sonnet 5 is the notable change since the last revision — it
  reaches roughly Opus-tier quality on reasoning-heavy work at Sonnet cost, which makes it the right
  default for the mid tier rather than a fallback.
- **Thinking:** Claude Opus 5 and Sonnet 5 think **by default**, and thinking shares the `max_tokens`
  budget with the answer — hence `CHAT_MAX_TOKENS` defaults to 4096. Set `CHAT_THINKING=disabled` to
  turn it off (accepted on Opus 5 only at effort `high` or below). `budget_tokens` is removed on both
  and returns a 400; so are `temperature` / `top_p` / `top_k`.
- **Streaming:** Laravel `StreamedResponse` with `Content-Type: text/event-stream`,
  `Cache-Control: no-cache`, **`X-Accel-Buffering: no`**, `flush()` after each chunk; the Blade page
  consumes it with a vanilla-JS `fetch` reader.
- **Prompt caching:** the system prompt is **frozen** (no interpolated subject, date or depth) and
  marked `cache_control: {type: "ephemeral"}` on Anthropic. Verify with
  `usage.cache_read_input_tokens > 0` on turn 2 — `messages.cache_read_tokens` records it per turn, and
  the admin transcript view surfaces the total.

---

## 10. Metrics that actually matter

Tracked (or directly derivable) today, surfaced on the admin dashboard:

- **% completing the first layer** — the activation number.
- **Layers cleared** and **checkpoint pass rate** — retry-to-pass improvement over time.
- **Average and maximum depth reached** per subject.
- **Concepts mastered** vs **open misconceptions**.
- **Completed descents** (surfaced).
- **Token spend per subject** — cost per completed layer.

Deliberately **not** tracked: messages sent, session length, time on site. All three can rise while
learning quality falls, and a product that optimises for them stops being a learning tool.

Still to instrument: next-day and seven-day recall, review completion rate, % resuming an existing subject,
and false-grading rate (needs a human-labelled sample of `checkpoint_attempts`).
