<?php

return [

    'social' => [],

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    |
    | One registry, two jobs. `native` is what the learner picks in the language
    | switcher (endonym — a Romanian looks for "Română", never "Romanian"), and
    | `prompt` is the English exonym the guide is told to teach in, because that
    | is the name a model resolves most reliably.
    |
    | Adding a language is this entry plus a `lang/{code}` directory. The first
    | entry is the default when nothing else is known.
    |
    */

    'locales' => [
        'en' => ['native' => 'English', 'prompt' => 'English'],
        'ro' => ['native' => 'Română', 'prompt' => 'Romanian'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Support
    |--------------------------------------------------------------------------
    |
    | Inbox that receives admin support requests, and the list of topics an
    | admin can pick from in the profile "Request support" form.
    |
    */

    'support_email' => env('SUPPORT_EMAIL', 'support@example.com'),

    'support_topics' => [
        'account',
        'technical',
        'billing',
        'content_moderation',
        'feature_request',
        'bug_report',
        'other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat — the descent
    |--------------------------------------------------------------------------
    |
    | Model routing, depth limits and cost guards for the rabbit-hole chat.
    | Turns are routed by depth + phase so free/shallow turns stay cheap and
    | only the deep layers reach for the premium model.
    |
    */

    'chat' => [
        // Which LlmClient AppServiceProvider binds: 'gemini' or 'anthropic'.
        'provider' => env('CHAT_PROVIDER', 'gemini'),

        // Depth tiers per provider. Gemini's free tier covers both Flash-Lite
        // and Flash, so the MVP runs at zero cost until billing is enabled.
        'models' => [
            'gemini' => [
                'cheap' => 'gemini-3.5-flash-lite',
                'mid' => 'gemini-3.5-flash-lite',
                'deep' => 'gemini-3.5-flash',
            ],
            'anthropic' => [
                'cheap' => 'claude-haiku-4-5',
                'mid' => 'claude-sonnet-5',
                'deep' => 'claude-opus-5',
            ],
        ],
        // Anthropic-only, optional: 'disabled' | 'adaptive'. Left unset the model
        // decides — note Claude Opus 5 / Sonnet 5 think by default, and thinking
        // shares the max_tokens budget with the answer, hence the headroom below.
        'thinking' => env('CHAT_THINKING'),

        'deep_threshold' => (int) env('CHAT_DEEP_THRESHOLD', 3),
        'max_depth' => (int) env('CHAT_MAX_DEPTH', 7),
        'max_tokens' => (int) env('CHAT_MAX_TOKENS', 4096),
        'grade_max_tokens' => (int) env('CHAT_GRADE_MAX_TOKENS', 1024),
        'summary_max_tokens' => (int) env('CHAT_SUMMARY_MAX_TOKENS', 512),
        'guest_daily_limit' => (int) env('CHAT_GUEST_DAILY_LIMIT', 10),
        'user_daily_limit' => (int) env('CHAT_USER_DAILY_LIMIT', 80),
        'history_limit' => (int) env('CHAT_HISTORY_LIMIT', 40),

        // Turns kept verbatim before the older ones are folded into a running
        // summary (summarizeContext). Keeps deep holes inside a cheap context.
        'summarize_after' => (int) env('CHAT_SUMMARIZE_AFTER', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Subjects — the learner's library
    |--------------------------------------------------------------------------
    |
    | How many subjects a page of the library holds, how many the sidebar keeps
    | in view, and how deep the folder tree may nest. The depth cap is a product
    | decision (a tree you can't see the bottom of stops being an organiser),
    | which is why it lives here and not in the schema.
    |
    */

    'subjects' => [
        'per_page' => (int) env('SUBJECTS_PER_PAGE', 25),
        'sidebar_limit' => (int) env('SUBJECTS_SIDEBAR_LIMIT', 12),
        'max_folder_depth' => (int) env('SUBJECTS_MAX_FOLDER_DEPTH', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sources — study a web page
    |--------------------------------------------------------------------------
    |
    | A learner can drop a URL in the composer and descend through *that page*.
    | The fetch is deliberately tightly bounded: a learning app has no business
    | holding a socket open, and an unbounded extract is an unbounded prompt.
    |
    */

    'sources' => [
        'timeout' => (int) env('SOURCE_TIMEOUT', 10),
        'max_bytes' => (int) env('SOURCE_MAX_BYTES', 2_000_000),
        'max_redirects' => (int) env('SOURCE_MAX_REDIRECTS', 3),
        // Words of the extract that reach the teaching prompt.
        'prompt_words' => (int) env('SOURCE_PROMPT_WORDS', 3000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mastery
    |--------------------------------------------------------------------------
    |
    | A concept moves to "mastered" once the learner has demonstrated it this
    | many times. Below that it stays "developing", and a concept the grader
    | caught a wrong belief about resurfaces every layer until it is re-proven.
    |
    */

    'mastery' => [
        'demonstrations_to_master' => (int) env('MASTERY_DEMONSTRATIONS', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rewards — what XP is actually for
    |--------------------------------------------------------------------------
    |
    | Every value here buys a *different* behaviour, which is the whole point:
    | a single flat award per layer makes "most XP" and "most layers cleared"
    | the same ranking twice, and rewards volume over depth.
    |
    |   layer          a cleared layer, plus `layer_depth_xp` for each layer of
    |                  depth already behind it — layer 06 is harder than layer 00
    |                  and is worth more.
    |   first_try      cleared without a failed attempt at that layer. Rewards
    |                  reading properly rather than guessing at the checkpoint.
    |   concept        a concept crossing into mastered (demonstrated twice).
    |   surfaced       a subject carried all the way to the bottom — the single
    |                  rarest thing a learner can do here, priced accordingly.
    |
    | These are ledger amounts. Changing one never rewrites history: xp_events
    | is append-only and users.xp is its running sum.
    |
    */

    'rewards' => [
        'layer_xp' => (int) env('REWARD_LAYER_XP', 50),
        'layer_depth_xp' => (int) env('REWARD_LAYER_DEPTH_XP', 10),
        'first_try_xp' => (int) env('REWARD_FIRST_TRY_XP', 25),
        'concept_xp' => (int) env('REWARD_CONCEPT_XP', 20),
        'surfaced_xp' => (int) env('REWARD_SURFACED_XP', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rankings
    |--------------------------------------------------------------------------
    |
    | The boards are aggregates over the xp_events ledger, so they are cheap but
    | not free — and nobody needs them to the second. `cache_ttl` is how long a
    | computed board is served from cache; a learner's own standing is always
    | computed live, because that is the number they are watching.
    |
    */

    'ranking' => [
        'per_board' => (int) env('RANKING_PER_BOARD', 25),
        'cache_ttl' => (int) env('RANKING_CACHE_TTL', 300),
    ],
];
