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

    /*
    |--------------------------------------------------------------------------
    | Methods — the reference desk
    |--------------------------------------------------------------------------
    |
    | The techniques the product is built on, one entry each at /methods/{slug}.
    | Three things live here and one deliberately does not.
    |
    | Here: the icon, the note grounding the entry in what this app actually
    | does, and the reading list. NOT here: the prose. That is written by the
    | guide, once per language, and stored in `method_notes` — see MethodLibrary.
    |
    | The reading lists are hand-checked and must stay that way. A model is
    | never allowed to be the source of a citation: it will produce a plausible
    | author, year and DOI for a paper that does not exist, on a page whose whole
    | claim is that you can go and check. The prompt says so explicitly, and this
    | list is what it is told to leave alone.
    |
    | `here` is English on purpose — it is prompt input, not UI copy, and the
    | model renders it into the learner's language. Names and one-line summaries
    | are translated (`frontend.methods.items.*`) because the index page must
    | render without touching a model at all.
    |
    | A method slug that matches a learning-mode slug (`socratic`) is what puts
    | the "What is …?" link on the mode chip in the workspace. That is the whole
    | mapping — no table.
    |
    */

    'methods' => [

        'sq3r' => [
            'icon' => 'menu_book',
            'here' => 'The five steps map onto the descent almost exactly: a question-first layer '
                .'poses the checkpoint before any lesson (Question), the teaching turn is the Read, '
                .'answering the checkpoint in the learner\'s own words is the Recite, and concepts the '
                .'grader caught a wrong belief about are woven back into later layers (Review). Survey '
                .'runs too, for a subject opened from a link: before layer 00 the guide maps the page '
                .'— its sections in its own words, what it assumes, where it is thin, and the questions '
                .'it is really answering — and explains none of it. That turn costs no depth and leaves '
                .'no checkpoint. A subject named rather than linked has no page to survey, so it starts '
                .'at layer 00.',
            'references' => [
                ['text' => 'Robinson, F. P. (1946). Effective Study. Harper & Brothers. — the original statement of the method.'],
                ['text' => 'Dunlosky, J., Rawson, K. A., Marsh, E. J., Nathan, M. J., & Willingham, D. T. (2013). Improving Students’ Learning With Effective Learning Techniques. Psychological Science in the Public Interest, 14(1), 4–58. — reviews SQ3R and rates it low-utility next to its own Recite step.',
                    'url' => 'https://doi.org/10.1177/1529100612453266'],
            ],
        ],

        'retrieval-practice' => [
            'icon' => 'quiz',
            'here' => 'Every layer ends on a checkpoint answered from memory, in the learner\'s own '
                .'words, and graded against named criteria. Nothing advances on "I understood that": '
                .'the next layer opens only on a graded answer, and the mastery map only moves on '
                .'graded evidence.',
            'references' => [
                ['text' => 'Roediger, H. L., & Karpicke, J. D. (2006). Test-Enhanced Learning: Taking Memory Tests Improves Long-Term Retention. Psychological Science, 17(3), 249–255.',
                    'url' => 'https://doi.org/10.1111/j.1467-9280.2006.01693.x'],
                ['text' => 'Karpicke, J. D., & Blunt, J. R. (2011). Retrieval Practice Produces More Learning than Elaborative Studying with Concept Mapping. Science, 331(6018), 772–775.',
                    'url' => 'https://doi.org/10.1126/science.1199327'],
                ['text' => 'Dunlosky, J., et al. (2013). Improving Students’ Learning With Effective Learning Techniques. Psychological Science in the Public Interest, 14(1), 4–58. — rates practice testing as one of two high-utility techniques.',
                    'url' => 'https://doi.org/10.1177/1529100612453266'],
            ],
        ],

        'spaced-repetition' => [
            'icon' => 'schedule',
            'here' => 'Partly. A concept the grader found a wrong belief about is resurfaced in later '
                .'layers rather than retaught cold, so it comes back after a gap and inside new '
                .'material. There is no cross-subject review schedule yet, and no card queue.',
            'references' => [
                ['text' => 'Cepeda, N. J., Pashler, H., Vul, E., Wixted, J. T., & Rohrer, D. (2006). Distributed practice in verbal recall tasks: A review and quantitative synthesis. Psychological Bulletin, 132(3), 354–380.',
                    'url' => 'https://doi.org/10.1037/0033-2909.132.3.354'],
                ['text' => 'Cepeda, N. J., Vul, E., Rohrer, D., Wixted, J. T., & Pashler, H. (2008). Spacing Effects in Learning: A Temporal Ridgeline of Optimal Retention. Psychological Science, 19(11), 1095–1102.',
                    'url' => 'https://doi.org/10.1111/j.1467-9280.2008.02209.x'],
                ['text' => 'Ebbinghaus, H. (1885). Über das Gedächtnis. Duncker & Humblot. — where the forgetting curve comes from.'],
            ],
        ],

        'desirable-difficulties' => [
            'icon' => 'fitness_center',
            'here' => '"Question me first" poses the checkpoint before the lesson, so the first attempt '
                .'is harder and often wrong on purpose — and the lesson stays one click away, costing '
                .'no depth. XP is weighted the same way: a layer cleared without a failed attempt is '
                .'worth more, and a deep layer is worth more than a shallow one.',
            'references' => [
                ['text' => 'Bjork, R. A., & Bjork, E. L. (2011). Making things hard on yourself, but in a good way: Creating desirable difficulties to enhance learning. In Psychology and the Real World.',
                    'url' => 'https://bjorklab.psych.ucla.edu/wp-content/uploads/sites/13/2016/04/EBjork_RBjork_2011.pdf'],
                ['text' => 'Bjork, R. A. (1994). Memory and metamemory considerations in the training of human beings. In J. Metcalfe & A. Shimamura (Eds.), Metacognition: Knowing about Knowing. MIT Press.'],
            ],
        ],

        'self-explanation' => [
            'icon' => 'psychology',
            'here' => 'The checkpoint never asks for a definition that could be copied back. It asks '
                .'the learner to explain the idea in their own words, apply it to a new case, or '
                .'predict an outcome — and the verdict then names, criterion by criterion, what was '
                .'shown and what was not.',
            'references' => [
                ['text' => 'Chi, M. T. H., de Leeuw, N., Chiu, M.-H., & LaVancher, C. (1994). Eliciting self-explanations improves understanding. Cognitive Science, 18(3), 439–477.',
                    'url' => 'https://doi.org/10.1207/s15516709cog1803_3'],
                ['text' => 'Dunlosky, J., et al. (2013). Improving Students’ Learning With Effective Learning Techniques. Psychological Science in the Public Interest, 14(1), 4–58. — covers self-explanation and elaborative interrogation, and is honest about how uneven the evidence is.',
                    'url' => 'https://doi.org/10.1177/1529100612453266'],
            ],
        ],

        'interleaving' => [
            'icon' => 'shuffle',
            'here' => 'Only partly, and it is worth saying plainly: subjects sit side by side in the '
                .'library and a learner moves between them freely, but the app does not yet schedule '
                .'a mix. Within one subject the descent is deliberately blocked, not interleaved.',
            'references' => [
                ['text' => 'Rohrer, D., & Taylor, K. (2007). The shuffling of mathematics problems improves learning. Instructional Science, 35(6), 481–498.',
                    'url' => 'https://doi.org/10.1007/s11251-007-9015-8'],
                ['text' => 'Rohrer, D., Dedrick, R. F., & Stershic, S. (2015). Interleaved practice improves mathematics learning. Journal of Educational Psychology, 107(3), 900–908.',
                    'url' => 'https://doi.org/10.1037/edu0000001'],
            ],
        ],

        'calibration' => [
            'icon' => 'balance',
            'here' => 'Before submitting a checkpoint the learner rates how sure they are. The verdict '
                .'compares that rating against the grade and says plainly whether they were well '
                .'calibrated, more confident than the answer supported, or knew more than they gave '
                .'themselves credit for.',
            'references' => [
                ['text' => 'Koriat, A., & Bjork, R. A. (2005). Illusions of Competence in Monitoring One’s Knowledge During Study. Journal of Experimental Psychology: Learning, Memory, and Cognition, 31(2), 187–194.',
                    'url' => 'https://doi.org/10.1037/0278-7393.31.2.187'],
                ['text' => 'Dunlosky, J., & Rawson, K. A. (2012). Overconfidence produces underachievement: Inaccurate self evaluations undermine students’ learning and retention. Learning and Instruction, 22(4), 271–280.',
                    'url' => 'https://doi.org/10.1016/j.learninstruc.2011.08.003'],
            ],
        ],

        'learning-by-teaching' => [
            'icon' => 'record_voice_over',
            'here' => 'The checkpoint is "explain it back in your own words" — the learner teaches the '
                .'layer to the guide, and what gets graded is that explanation, never a multiple '
                .'choice. A concept only reaches "mastered" after it has been demonstrated more than '
                .'once.',
            'references' => [
                ['text' => 'Fiorella, L., & Mayer, R. E. (2013). The relative benefits of learning by teaching and teaching expectancy. Contemporary Educational Psychology, 38(4), 281–288.',
                    'url' => 'https://doi.org/10.1016/j.cedpsych.2013.06.001'],
                ['text' => 'Nestojko, J. F., Bui, D. C., Kornell, N., & Bjork, E. L. (2014). Expecting to teach enhances learning and organization of knowledge in free recall of text passages. Memory & Cognition, 42(7), 1038–1048.',
                    'url' => 'https://doi.org/10.3758/s13421-014-0416-z'],
            ],
        ],

        'socratic' => [
            'icon' => 'forum',
            'here' => 'One of the six teaching modes. In Socratic mode the guide leads with questions '
                .'rather than answers, and the mode travels in the teaching directive for every layer '
                .'of that subject — it is chosen once, in the composer, and it holds all the way down.',
            'references' => [
                ['text' => 'Chi, M. T. H., Siler, S. A., Jeong, H., Yamauchi, T., & Hausmann, R. G. (2001). Learning from human tutoring. Cognitive Science, 25(4), 471–533.',
                    'url' => 'https://doi.org/10.1207/s15516709cog2504_1'],
                ['text' => 'Graesser, A. C., Person, N. K., & Magliano, J. P. (1995). Collaborative dialogue patterns in naturalistic one-to-one tutoring. Applied Cognitive Psychology, 9(6), 495–522.'],
                ['text' => 'Paul, R., & Elder, L. (2007). The Thinker’s Guide to the Art of Socratic Questioning. Foundation for Critical Thinking.'],
            ],
        ],
    ],
];
