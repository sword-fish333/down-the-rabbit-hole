<?php

return [

    'social' => [],

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
        'models' => [
            'cheap' => env('CHAT_MODEL_CHEAP', 'claude-haiku-4-5'),
            'mid' => env('CHAT_MODEL_MID', 'claude-sonnet-4-6'),
            'deep' => env('CHAT_MODEL_DEEP', 'claude-opus-4-8'),
        ],
        'deep_threshold' => (int) env('CHAT_DEEP_THRESHOLD', 3),
        'max_depth' => (int) env('CHAT_MAX_DEPTH', 7),
        'max_tokens' => (int) env('CHAT_MAX_TOKENS', 2048),
        'guest_daily_limit' => (int) env('CHAT_GUEST_DAILY_LIMIT', 10),
        'user_daily_limit' => (int) env('CHAT_USER_DAILY_LIMIT', 80),
        'layer_xp' => (int) env('CHAT_LAYER_XP', 50),
        'history_limit' => (int) env('CHAT_HISTORY_LIMIT', 40),
    ],
];
