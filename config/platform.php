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
];
