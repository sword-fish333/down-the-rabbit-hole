# Down the Rabbit Hole — Product & Roadmap

> Source of truth for **what the product is** and **what's next**. Engineering conventions live in
> [`CLAUDE.md`](../CLAUDE.md); this file does not repeat them.
>
> For a fast answer to "what's built?", read [§9 Status](#9-status--roadmap). Update that table with
> every shipped slice.

---

## 1. What this is

An AI **deep-learning** app, not a chat app. You **name a subject — or drop in a link — fall in, and at
each layer the guide makes you prove you understood it before the next, deeper layer opens.**

The spine is the *descent*: one conversation per subject with a measurable **depth**. Every reward is
anchored to **depth of learning**, never time on app. The design language is Blade-Runner noir × Alice
in Wonderland; the principles are **deep work, flow and mastery** (clear goals → immediate feedback →
challenge/skill balance).

**The loop:** `name a subject → the guide teaches one layer → "prove it" checkpoint → pass → descend`.

A learner can also invert it — **Question me first** poses the checkpoint cold, before any teaching, and
the lesson is there only if they ask for it. Retrieval before instruction is the stronger way to learn
and the faster way to quit, so it is a choice and `guided` is the default.

---

## 2. Product language

The words are fixed, because consistency across every touchpoint is what makes progress quotable —
"I got 7 layers deep on quantum mechanics" is a sentence a learner will screenshot.

| Concept | Term |
|---|---|
| Progression unit | **Layer**, always two digits: `Layer 04` |
| Action | **Descend** / **Go deeper** |
| Comprehension gate | **Prove it** |
| Progress | **Depth reached: Layer 4 of 9** |
| Return mechanic | **12-day descent** — never "streak" in the UI |
| How a layer opens | **Teach me first** / **Question me first** — never "test" or "quiz" |
| Personal best | **Your deepest dive: [subject] — 7 layers** |
| The thing being learned | **Subject** (the model is still `Conversation`; the product never says "hole") |

Tagline: *Learn anything, all the way down.* Alternates held in reserve: "Depth over breadth", "No
skimming. Only depth.", "Understanding has layers. You earn each one."

---

## 3. The signature mechanic — prove it to descend

`DescentService` drives a small state machine (`exploring` → `checkpoint_pending` → `surfaced`). The
load-bearing design decision is that its **two turns use two different transports**:

1. **Teach turn** — `LlmClient::streamTeachingTurn()`. Streamed Markdown, rendered as it arrives. The
   frozen system prompt teaches exactly one layer for the current depth, then poses **one concrete
   checkpoint** (explain-back / apply-to-a-new-case / predict — never trivia), ending on a
   `**Checkpoint:**` line. State → `checkpoint_pending`. The learner can ask for the *same* layer from a
   different angle — *explain differently*, *give an analogy*, *show the evidence*, *challenge me* —
   without advancing.
2. **Grade turn** — `LlmClient::gradeCheckpoint()`. A **separate, non-streaming, JSON-Schema-constrained**
   call returning a `GradingResult`. On a pass: `current_depth++`, `LayerCompleted` fires (→ XP +
   descent), and the mastery map updates. On a retry the learner keeps their depth and their progress.

Grading is schema-enforced server-side rather than parsed out of prose, because a pedagogically perfect
answer can still carry malformed JSON and the failure mode is silent. `GradingResult::fromArray()`
clamps every field and a provider that ignores the schema degrades to a safe `retry`. **A grading call
that fails outright never costs the learner their layer.**

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

`CHAT_PROVIDER` binds `GeminiClient` or `AnthropicClient`. `LlmStream` is iterated for text deltas and
then read for `text()` / `usage()`, so a learner who disconnects mid-turn still gets their partial layer
persisted. `summarizeContext()` runs in a queued job (`CompactConversationContext`) once a subject
outgrows its verbatim window, keeping a deep subject affordable without putting a second round-trip on
the critical path.

### Mastery map

`Concept` rows carry one of four states — **mastered**, **developing**, **misunderstood**,
**not explored** — and `MasteryService` only ever moves them on **graded evidence**. A concept becomes
mastered after N demonstrations (`platform.mastery.demonstrations_to_master`) and never regresses out of
mastery. A misconception is recorded together with the belief that produced it, and the next teaching
turn is asked to weave a correction in. That is the spaced-review mechanic: driven by evidence, not a
timer.

### Learning modes

Six seeded modes — Socratic, Visual explanation, Exam preparation, Project-based, Fast overview, Deep
technical descent — each carrying a `prompt_directive` appended to the per-turn teaching instruction.
Fully CRUD-managed from the admin panel, so the pedagogy is tunable without a deploy.

### Honest progress

The teaching stream carries `stage` frames alongside `token`. Each is emitted at the moment that work
actually happens and carries the numbers behind it — *"Reading X — 1,200 words"*, *"Recalling 4 concepts
you've proven"*, *"Composing Layer 04"*. The workspace renders them as a collapsible descent, then
reveals prose a completed block at a time: committed blocks land in stable DOM once and animate once,
and only the unfinished tail re-renders per frame.

**No stage is ever emitted on a timer, or for work that did not occur.** A subject with no source never
claims to be reading one. This is the difference between progress and a spinner with captions.

---

## 4. The learner's library

The second reason to open the app: not just descending, but keeping track of what you have been curious
about. The model is a personal vault, closer to Obsidian than to a chat history.

- **The subject rail** — on every app screen. One primary action (*New descent*), one list, two
  destinations. Toggles between most-recent-first and the learner's folder tree; the choice travels in a
  cookie so the tree is only queried when it is actually on screen. A visitor sees a sign-in card in the
  same slot, because the descent remembers nothing until it has somewhere to remember it.
- **`/subjects`** — the whole library. Cursor pagination (a keyset scan, not offset), search across the
  typed subject and its title, four state filters, select-mode bulk delete, and infinite scroll. One
  Blade partial renders the rows for both first paint and the `fragment=1` lazy-load, so no row markup
  exists in JavaScript.
- **`/subjects/organization`** — the organiser. A folder tree with drag-and-drop filing, folders inside
  folders (capped by `platform.subjects.max_folder_depth`), rename, and a client-side filter that
  reveals matches inside collapsed folders. **Deleting a folder never deletes a subject** — they fall
  back to unfiled, guaranteed by the schema rather than by care.
- **Read-only sharing** — `share_token` on a subject *is* the capability. `/s/{token}` is a
  server-rendered public page; unsharing nulls the token and the old link dies with it.

Every mutation here is a real form post to a validated route. Drag-and-drop fills in forms the server
already rendered rather than calling endpoints of its own, which is why the surface still works with
JavaScript off — and why the server, not the browser, decides whether a move is legal.

---

## 5. Source-grounded learning

**The feature:** a learner drops a **URL** into the composer and descends through *that page*. It turns
"explain this subject" into "make me understand *this specific material*", which is what course notes,
papers and articles actually demand — and it is the most defensible thing to put behind a paid tier.

### What is built

`DescentService::open()` reads the composer input. A URL is fetched by `SourceFetcher`, extracted to
prose, stored as a `Source`, and re-sent inside **every** teaching directive — not left in the message
history, which gets compacted away as a subject deepens. Anything typed alongside the link becomes the
subject name; otherwise the page's own title does. The guide is instructed to teach from the material,
to say plainly where it steps beyond the page, and to say when the page is thin or wrong.

| Table | Columns |
|---|---|
| `sources` | `conversation_id`, `url`, `title`, `site`, `text`, `words` |

**What `SourceFetcher` guarantees.** The URL comes from a visitor and the fetch runs inside the network,
so this is the security boundary of the feature. Every host is resolved and checked against private and
reserved ranges **before a socket opens**, and re-checked on every redirect hop (Guzzle `on_redirect`).
The fetch is bounded by timeout, bytes and hops; only HTML/text is accepted; a page with under 100
readable words is refused rather than taught from. Extraction is regex-based on purpose — the guide
reads prose, not structure, and that is not worth a dependency on the critical path.
`SourceFetcherTest` asserts *no request is sent* for each refused host; that assertion is the point of
the test, not a detail of it.

### What is next

Chunking and citations, in this order:

1. `source_chunks` — ordered extracts with a stable `locator` (heading / char range), the anchor a
   citation points at. Retrieval starts as **BM25/FTS over the chunks**: good enough to ship and it
   avoids an embedding provider on day one. Vector search is an optimisation, not a prerequisite.
2. `TeachingRequest` gains a `sources` array of retrieved extracts; the guide cites by locator.
3. `GradingSchema` gains an optional `evidence` field, so a checkpoint can require the learner to point
   at the passage that supports their answer.
4. Document/PDF upload. A scanned PDF with no text layer must **fail loudly** — OCR is out of scope, and
   silently producing nothing would put unreliable grounding under a trustworthy grader.

The citation popover styling (`.dth-source-popover` — native Popover API + CSS anchor positioning, with
a bottom sheet on narrow screens) is already in the stylesheet, unused until step 2 lands. The *show the
evidence* reframe currently asks the guide to name what the layer rests on; it becomes a real citation
surface at the same time.

---

## 5b. Language — the interface and the guide

**Two languages, and they are not the same language.** The *interface* is localised the usual way. The
*teaching* language is a property of the subject, because it is content: a learner can read the app in
English and descend through a Romanian subject, and the descent must not change language at layer 4.

| Layer | Where it lives | How it is decided |
|---|---|---|
| Interface | `lang/{en,ro}`, `SetLocale` | session → `users.locale` → 1-year cookie → `Accept-Language` |
| Teaching + verdicts | `conversations.locale` | the locale the subject was opened in, re-asserted every turn |

**Why the browser gets the first guess.** Most visitors never open a language menu. `Accept-Language`
negotiation is what makes a Romanian visitor land on a Romanian page, and it is the single biggest
conversion lever in this feature — the switcher is the fallback, not the mechanism. Signing in copies the
choice onto the account so it travels to the next device; `admin*` is excluded and stays English.

**Why a per-turn directive and not a system prompt line.** The system prompt is frozen for cache
stability, and a model obeys the instruction it saw *last*. Left implicit, a model drifts back to the
language of its instructions — English — around layer 3, and drifting mid-descent reads as a bug.
`DescentPrompt::languageNote()` therefore re-sends `[LANGUAGE] …` on every teaching and reframe turn, and
`GradingRequest::$language` carries the same instruction into the verdict, which is prose the learner
reads. The guide is told to follow the learner over the directive if they write in another language: the
stored locale is the opening bid, not a lock.

**The one thing that must never translate** is the literal `**Checkpoint:**` marker —
`DescentService::currentCheckpoint()` splits on it and `markdown.js` styles it, so a helpfully translated
marker would silently break checkpoint grading. It is pinned in the system prompt alongside code.

Adding a language is one entry in `config/platform.php` → `locales` plus a `lang/{code}` directory.
`LocaleTest` asserts **key parity and placeholder parity** across locales, because `fallback_locale`
means a missing key renders the English string — a half-translated page looks deliberate and ships.
Learner-facing copy from the database (learning modes) resolves through `LearningMode::label()`: only
non-default locales carry `frontend.modes.*` keys, so English keeps following what an admin types.

**Deliberately not done:** locale-prefixed URLs. Language lives in a cookie, so the landing page is
indexed in one language only. `/{locale}/…` route prefixes plus `hreflang` are the upgrade, and the
trigger for it is organic non-English traffic being worth the churn through every `route()` call.

---

## 5bb. Survey — the step that was missing

SQ3R maps almost exactly onto the descent already: **Question** is a question-first layer, **Read** is
the teaching turn, **Recite** is answering the checkpoint in your own words, **Review** is a fumbled
concept resurfaced in a later layer. **Survey** was the one step with nothing behind it — and it is the
only one that needs a text, so it belongs to the source-grounded path and nowhere else.

For a subject opened from a link, the first turn now maps the page instead of teaching it: its sections
in its own words, what it assumes the reader knows, where it is thin or dated, and the two or three
questions the page is really answering. It explains nothing — a learner must be able to read it and
still not know the subject, because the point is to walk in holding questions rather than answers.

Everything about it is derived, not asked for. `DescentService::awaitsSurvey()` is four conditions —
a source, depth 0, no layer opened, no survey written — and `turnPhase()` picks it up the same way it
picks up a cold question, so the browser triggers it with the same argument-less stream call. It costs
**no depth and leaves no checkpoint**: `PHASE_SURVEY` is not in `OPENING_PHASES`, so `currentCheckpoint()`
cannot read it and the subject stays `exploring`. That is also the fail-safe — the directive forbids a
checkpoint line, and the phase makes it harmless if a model writes one regardless.

The fourth condition is the one that matters in production: a grounded subject already past layer 00
must never be interrupted by a map of ground it has walked, which is exactly the state every source
subject was in when this shipped.

Its marker in the transcript carries the only in-session link to `/methods/sq3r` — a step named after
its method can explain itself.

**Still missing from SQ3R:** nothing, for a linked subject. For a *named* subject there is no text to
survey, and inventing one would be the guide teaching layer 00 early under another name.

---

## 5c. The reference desk — `/methods`

Every screen in this product makes a pedagogical claim: prove it to descend, question me first, rate
your confidence before you submit. A claim like that is worth nothing unless it can be checked, so each
technique has a public entry at `/methods/{slug}` — what it is, why it works, what the evidence does
*not* support, and where you meet it here.

**The page has two authors and says so.** The prose is written by the guide, once per (method, language),
and stored in `method_notes`; the **reading list is hand-written** and lives in `config/platform.php` →
`methods`, beside a `here` note that grounds the entry in what the app actually does. That split is the
whole design. A model will produce a plausible author, year and DOI for a paper that does not exist —
on a page whose entire claim is *go and check*. The system prompt therefore forbids producing citations
at all, and the list is rendered underneath from config. Delete a row from `method_notes` and the next
visitor rewrites it; that is the regeneration story, and it is why there is no admin screen for it.

`MethodLibrary` adds **no fourth verb** to `LlmClient` — an entry is prose from a prompt, which is what
`streamTeachingTurn` already is, drained rather than streamed because nobody watches a reference page
type itself. It is generated at `mid`, not `cheap`: written once, read by everyone.

**Where it is linked from, and where it deliberately is not.** The footer, and one muted line under the
approach picker on the composer. A learning mode whose slug matches a method slug (`socratic`) turns its
chip in the workspace header into the link — no mapping table, no extra pixels, and `target="_blank"`,
because reading about method while a checkpoint is open is the most respectable way to procrastinate
there is. Nothing links here from inside an open checkpoint.

`lang/{code}` carries each method's **name, one-line summary and a glossary of the product's own words**.
The name and summary let the index render without touching a model at all; the glossary is prompt input
— without it the first Romanian entry called a *coborâre* a "descentrare".

---

## 6. Frontend architecture

Blade + vanilla JS, no SPA framework. Server-rendered throughout, so every public page is crawlable
without a second rendering path.

### Motion system

Durations and easings are **named for the job** in `resources/css/app.css`, never scattered as arbitrary
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

### Signature interactions

1. **Lock-on** — a one-time cyan border trace when a composer takes focus, then silence.
2. **Semantic light trail** — when a layer unlocks, a short line travels from the cleared layer to the
   newly opened one on the depth rail, showing causality, then removes itself.
3. **The descent dot** — a dot travelling down a hairline shaft while the guide works: the light trail's
   gesture at the scale of one turn, and the only looping animation on the workspace.
4. **Focus aperture** — deep-work mode drops peripheral contrast and concentrates luminance on the
   reading column. Contrast only; the interface is never blurred, because blurred UI is unusable UI.

### Accessibility & performance

- WCAG 2.2 AA target: skip link, one polite live region for every status announcement, real ARIA
  tablist/radiogroup/switch semantics, `:focus-visible` throughout, keyboard parity with hover on every
  card, and **no state conveyed by colour alone** — every mastery and status token also carries an icon
  and a label, and `misunderstood` additionally carries a pattern.
- `prefers-reduced-motion` and `prefers-reduced-transparency` have designed static fallbacks, not broken
  ones: every animation has a meaningful end state it simply snaps to.
- The reading column is capped at ~68 characters (`.measure`). It is the single most load-bearing
  typographic decision in the product.
- Markdown is rendered by a ~180-line XSS-safe renderer for the live stream and
  `Str::markdown(html_input: escape)` for persisted turns — matching output, no dependency.
- Long transcripts use `content-visibility: auto`. Streaming re-renders at most once per frame and never
  moves text the learner has already read.
- Composer fields size themselves with native `field-sizing: content`, with a guarded JS fallback; no
  script writes a height for an element that is not on screen.

---

## 7. Product strategy

### Gamification — reward depth, not time

- **XP** for cleared layers, with the `xp_events` ledger as the source of truth. Diminishing returns per
  action per day, to kill grinding.
- **The descent** (the return mechanic): the unit is **one cleared layer per day** — a learning
  equivalent, mirroring Duolingo's "one lesson, not XP" insight.
- **Progressive disclosure:** during deep work the UI shows only the objective, the depth and the
  progress; the learning record lives on the profile. The chrome shows a descent count **only while it
  is alive** — no zero-state guilt, no loss-anxiety mechanics.
- **Deferred:** leagues, quests, badges, reputation, levels. All bolt onto the same event stream later.
- **Never optimise for time on site.** The admin dashboard deliberately does not track messages sent or
  session length; both can rise while learning quality falls.

### Monetization — protect margins against real LLM cost

- Launch **freemium plus a single ~$20 Pro tier**. Do not over-engineer tiers.
- **Meter the expensive path:** daily turn credits, model routing (cheap for grading, mid for shallow
  teaching, deep for the deepest layers), prompt caching of the frozen system prompt, context compaction
  on long subjects, hard spend caps.
- The paid tier sells **depth and durability**, not access to knowledge: higher daily limits, source
  ingestion (§5), longer subjects, export, the weekly mastery report. **Never pay-to-win on knowledge** —
  money buys convenience and more AI, never exclusive learning.
- **No Stripe / Cashier yet.** Gate with the daily counter until the tier actually launches.

### Growth

- Shared subject pages are already server-rendered, so the UGC + SEO loop needs only per-page OG images
  and a sitemap on top of what exists.
- Later: double-sided referral (bonus credits both sides), topic communities.

---

## 8. LLM & cost

- **Current provider: Google Gemini** — `CHAT_PROVIDER=gemini`, one key (`GEMINI_API_KEY`).
  `GeminiClient` hits `v1beta/models/{model}:streamGenerateContent?alt=sse` for teaching, and
  `:generateContent` with `responseMimeType: application/json` + `responseJsonSchema` for grading.
  System prompt in `systemInstruction`, assistant role renamed to `model`, `thought` parts dropped,
  implicit caching.
- **Gemini pricing** (per 1M tokens, in / out, paid tier): `gemini-3.5-flash-lite` **$0.30 / $2.50**
  (cheap + mid), `gemini-3.5-flash` **$1.50 / $9** (deep). Both free on the free tier.
- **Alternate provider:** `CHAT_PROVIDER=anthropic` → `AnthropicClient` (`ANTHROPIC_API_KEY`).
  Structured outputs go in `output_config.format`; the older top-level `output_format` is deprecated.
- **Claude pricing** (per 1M tokens, in / out):

  | Model | ID | Context | Input | Output |
  |---|---|---|---|---|
  | Claude Opus 5 | `claude-opus-5` | 1M | $5 | $25 |
  | Claude Sonnet 5 | `claude-sonnet-5` | 1M | $3 (intro $2 through 2026-08-31) | $15 (intro $10) |
  | Claude Haiku 4.5 | `claude-haiku-4-5` | 200K | $1 | $5 |

  Routed by depth + phase: **Haiku 4.5** for grading and summarising, **Sonnet 5** for shallow teaching,
  **Opus 5** past `CHAT_DEEP_THRESHOLD`.
- **Thinking:** Opus 5 and Sonnet 5 think by default, and thinking shares the `max_tokens` budget with
  the answer — hence `CHAT_MAX_TOKENS` defaults to 4096. `CHAT_THINKING=disabled` turns it off (accepted
  on Opus 5 only at effort `high` or below). `budget_tokens`, `temperature`, `top_p` and `top_k` are
  rejected on both.
- **Streaming:** `StreamedResponse` with `Content-Type: text/event-stream`, `Cache-Control: no-cache`,
  **`X-Accel-Buffering: no`**, and a `flush()` after each frame. The page consumes it with a vanilla
  `fetch` reader.
- **Prompt caching:** the system prompt is **frozen** — no interpolated subject, date or depth — and
  marked `cache_control: {type: "ephemeral"}` on Anthropic. Verify with
  `usage.cache_read_input_tokens > 0` on turn two; `messages.cache_read_tokens` records it per turn and
  the admin transcript view surfaces the total.
- **Source cost:** a grounded subject re-sends up to `platform.sources.prompt_words` of extract per
  teaching turn. At Flash-Lite rates a full seven-layer descent through a 3,000-word article costs
  roughly a cent. Chunked retrieval (§5) reduces it further; it is not needed to control it.

---

## 9. Status & roadmap

Legend: ✅ done · 🟡 in progress · ⬜ todo. **115 tests, all offline** (`FakeLlmClient`, `Http::fake`) —
no test touches the network.

### Stage 0 — the descent loop

| Status | Item |
|---|---|
| ✅ | `conversations` + `messages` — depth, per-layer status, running summary |
| ✅ | `DescentService` — depth/checkpoint state machine, reframes, model routing, `open()` from the composer |
| ✅ | `ChatStreamingService` — SSE `stage` / `token` / `done` / `error`, persists on disconnect |
| ✅ | Normalized `LlmClient` — `GeminiClient` + `AnthropicClient` behind three verbs |
| ✅ | Schema-enforced grading — `GradingSchema` + `GradingResult` |
| ✅ | Mastery map — `concepts` from graded evidence; misconceptions resurface |
| ✅ | `checkpoint_attempts` — criterion-by-criterion feedback, confidence calibration |
| ✅ | Learning modes — 6 seeded, full admin CRUD, wired into the teaching directive |
| ✅ | Context compaction — queued `CompactConversationContext` |
| ✅ | Gamification foundation — `xp_events`, `streaks`, `LayerCompleted` → `AwardLayerRewards` |
| ✅ | Cost control — daily turn counter, model routing, prompt caching, compaction |
| ✅ | Ungated first descent — anonymous guest subject, tracked in the session, claimed on sign-up |
| ✅ | Web-guard auth — register / login / logout / Google, queued email verification, learner profile |
| ✅ | Learning workspace — depth rail, mastery map, deep-work mode, staged reveal, thinking panel |
| ✅ | Admin — learning-health dashboard, learning-mode CRUD, learner management, subject oversight |

### Stage 1 — retention & utility

| Status | Item |
|---|---|
| ✅ | Subject library — `/subjects`, cursor pagination, search, filters, bulk delete, lazy load |
| ✅ | Organisation — `subject_folders` tree, drag-and-drop filing, nested folders, filter |
| ✅ | Persistent subject rail — recents or folder tree, on every app screen |
| ✅ | Source-grounded learning, URL half — SSRF-guarded fetch, extraction, grounded teaching |
| ✅ | Localisation — EN + RO interface, `Accept-Language` negotiation, per-subject teaching language |
| ✅ | The reference desk — `/methods`, entries written once per language, hand-checked reading lists |
| ✅ | Survey — SQ3R's first step for a linked subject: the page mapped before layer 00, no depth, no checkpoint |
| ⬜ | Locale-prefixed URLs + `hreflang`, when non-English organic traffic justifies it |
| 🟡 | Source chunking + citations — `source_chunks`, locators, FTS retrieval, `evidence` in the schema |
| ⬜ | Document/PDF upload |
| ⬜ | Flashcards from the mastery map · notes & highlights · end-of-subject synthesis · export |
| ⬜ | Cross-subject spaced review · weekly mastery report · learning paths with prerequisites |

### Stage 2 — engagement & first revenue

| Status | Item |
|---|---|
| ✅ | Public shareable subject pages — `share_token` capability, `/s/{token}`, server-rendered |
| ✅ | Question-first layers — `conversations.approach`, derived turn phase, lesson on demand |
| ✅ | Behaviour-differentiated XP — depth-scaled layers, first-try, mastered concept, completed subject |
| ✅ | Rankings — six boards × three windows over `xp_events`, opt-in, with public learner records |
| ✅ | Rankings: `(type, created_at)` index, versioned board cache, and the distance to one place up |
| ⬜ | Per-page OG images + sitemap for the shared pages |
| ⬜ | Achievements on the same event stream |
| ⬜ | Double-sided referral |
| ⬜ | Stripe / Cashier, when the paid tier launches |

### Stage 3 — UGC & scale

⬜ Fork another learner's subject · ⬜ Curated expert learning paths · ⬜ Collaborative study rooms ·
⬜ Resource voting & reputation · ⬜ BYOK · ⬜ Creator monetization.

**Deliberately delayed** until the metrics in §10 show learners clearing layers and returning to review:
leagues, cosmetic stores, creator monetization.

**On the rankings.** They rank *evidence* — rows in the `xp_events` ledger — never live state and never
time spent, and standing on one is opt-in because it publishes a name and a picture. Six boards rather
than one on purpose: a single ranking has one winner and a long tail who will never catch them, while
six say there is more than one way to be good at this. Monthly and weekly windows exist so joining today
is not joining a race decided a year ago.

---

## 10. Metrics that matter

Tracked or directly derivable today, surfaced on the admin dashboard:

- **% clearing the first layer** — the activation number.
- **% of subjects opened question-first**, and whether they clear layers at a different rate.
- **Layers cleared** and **checkpoint pass rate** — retry-to-pass improvement over time.
- **Average and maximum depth reached** per subject.
- **Concepts mastered** vs **open misconceptions**.
- **Completed descents** (surfaced).
- **Token spend per subject** — cost per cleared layer.

Deliberately **not** tracked: messages sent, session length, time on site. All three can rise while
learning quality falls, and a product that optimises for them stops being a learning tool.

Still to instrument: next-day and seven-day recall, review completion rate, % resuming an existing
subject, and false-grading rate — the last needs a human-labelled sample of `checkpoint_attempts`.
