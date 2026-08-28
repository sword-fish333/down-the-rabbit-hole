<?php

return [

    'meta' => [
        'description' => 'Name a subject — or drop in a link — and fall in. Each layer asks you to prove you understood it before the next one opens, until you surface an expert.',
    ],

    'general' => [
        'skip-to-content' => 'Skip to content',
        'dismiss' => 'Dismiss',
        'required' => 'required',
        'cancel' => 'Cancel',
        'submit-shortcut' => 'Enter starts a new line. Command, Control, or Option plus Enter submits the form.',
        'submit-shortcut-visual' => 'Enter = new line · ⌘ / Ctrl / Option + Enter = submit',
    ],

    'navbar' => [
        'primary' => 'Primary',
        'language' => 'Language',
        'theme-toggle' => 'Toggle light / dark',
        'sign-in' => 'Sign in',
        'start' => 'Start a descent',
        'sign-out' => 'Sign out',
        'subjects' => 'Subjects',
        'rankings' => 'Rankings',
        'public-record' => 'Your public record',
        'profile' => 'Profile',
        'account' => 'Account menu',
        'streak-title' => 'Days in a row with a layer cleared',
        'descent-days' => '{1} :count-day descent|[2,*] :count-day descent',
    ],

    /*
    |--------------------------------------------------------------------------
    | The subject rail
    |--------------------------------------------------------------------------
    |
    | Present on every app screen. One primary action, one list, two
    | destinations — and, for a visitor, one honest reason to sign in.
    |
    */

    'sidebar' => [
        'label' => 'Your subjects',
        'open' => 'Open the subject rail',
        'close' => 'Close the subject rail',
        'new' => 'New descent',
        'subjects' => 'Subjects',
        'view-label' => 'How to show your subjects',
        'view' => [
            'list' => 'Most recent first',
            'folders' => 'By folder',
        ],
        'empty' => 'Nothing yet. Name a subject and it will be waiting here.',
        'all' => 'All subjects',
        'organize' => 'Organise',
        'guest-title' => 'Keep what you learn',
        'guest-body' => 'Sign in and your subjects, your mastery map and your descent are still here tomorrow.',
        'guest-cta' => 'Sign in',
    ],

    /*
    |--------------------------------------------------------------------------
    | How a layer opens
    |--------------------------------------------------------------------------
    |
    | Shared between the composer and the workspace, because it is one choice
    | wherever it is made. Two options, each named for what arrives rather than
    | for the pedagogy behind it — "retrieval practice" is the reason, not the
    | offer.
    |
    */

    'approach' => [
        'legend' => 'How should each layer open?',
        'next-label' => 'How the next layer opens',
        'guided' => 'Teach me first',
        'guided-hint' => 'The guide teaches the layer, then you prove you got it.',
        'question' => 'Question me first',
        'question-hint' => 'The checkpoint arrives cold. Answer from what you know — or go and find out.',
        'switched-guided' => 'From the next layer on, the guide teaches before it asks.',
        'switched-question' => 'From the next layer on, the checkpoint comes first.',
        'posed' => 'Asked before it was taught',
        'teach-cta' => 'Teach me this layer',
        'teach-hint' => 'Your depth and the open checkpoint are untouched.',
        'why' => 'Why does being asked first work?',
    ],

    'auth' => [
        'register-title' => 'Create your account',
        'register-subtitle' => 'Save your descents, keep your mastery map, and pick up exactly where you stopped.',
        'login-title' => 'Welcome back',
        'login-subtitle' => 'Pick up where you left off.',
        'first-name' => 'First name',
        'last-name' => 'Last name',
        'first-name-placeholder' => 'Alice',
        'last-name-placeholder' => 'Liddell',
        'email' => 'Email',
        'password' => 'Password',
        'password-confirm' => 'Confirm password',
        'remember' => 'Keep me signed in',
        'register-cta' => 'Create account',
        'login-cta' => 'Sign in',
        'have-account' => 'Already have an account?',
        'no-account' => 'New here?',
        'go-login' => 'Sign in',
        'go-register' => 'Create one',
        'welcome' => 'Welcome to the rabbit hole.',
        'welcome-back' => 'Welcome back.',
        'signed-out' => 'Signed out.',
        'invalid-credentials' => 'Those credentials don’t match our records.',
        'account-blocked' => 'This account has been disabled.',

        // Brand showcase (desktop split panel)
        'brand-headline' => 'Fall in. Surface an expert.',
        'brand-subline' => 'A focused descent through any subject — prove each layer to unlock the next.',
        'feature-depth' => 'Go deeper, one layer at a time',
        'feature-prove' => 'Prove it before you progress',
        'feature-mastery' => 'Watch your mastery map fill in',

        // OAuth + fields
        'or' => 'or',
        'continue-with-google' => 'Continue with Google',
        'toggle-password' => 'Show or hide password',
        'google-failed' => 'Google sign-in didn’t complete. Please try again.',
        'google-error' => 'Google OAuth error (learner guard): :error',
        'email-placeholder' => 'you@example.com',
        'email-hint' => 'We’ll send one confirmation email. No newsletters.',
        'password-placeholder' => '••••••••',
        'password-hint' => 'At least 8 characters, with a letter and a number.',

        // Email confirmation
        'verify-title' => 'Confirm your email',
        'verify-body' => 'We sent a confirmation link to :email. It keeps your account and your progress yours — but you can keep learning right now either way.',
        'verify-banner' => 'Confirm :email to secure your account and your descent.',
        'verify-resend' => 'Resend the link',
        'verify-skip' => 'Keep learning for now',
        'verify-sent' => 'Confirmation link sent.',
        'verify-done' => 'Email confirmed. Thank you.',
        'verify-already' => 'That email is already confirmed.',
        'verify-invalid' => 'That confirmation link is no longer valid. Request a new one.',
    ],

    'mail' => [
        'verify-subject' => 'Confirm your email · :app',
        'verify-heading' => 'One click and you’re set, :name',
        'verify-body' => 'Confirm your email address so your subjects, mastery map and descent stay attached to your account.',
        'verify-cta' => 'Confirm email address',
        'verify-expiry' => 'The link is valid for :minutes minutes.',
        'verify-ignore' => 'If you didn’t create an account, you can safely ignore this message.',
        'sign-off' => 'See you down there,',
    ],

    'footer' => [
        'tagline' => 'Learn anything, all the way down.',
        'rights' => 'All rights reserved.',
        'blurb' => 'A deep-work learning tool, not a feed. You name a subject, the guide teaches one layer, and you prove you understood it before the next one opens.',
        'learn' => 'Learn',
        'how' => 'How it works',
        'start-descent' => 'Start a descent',
        'your-subjects' => 'Your subjects',
        'your-record' => 'Your learning record',
        'methods' => 'The methods behind it',
        'create-account' => 'Create an account',
        'sign-in' => 'Sign in',
        'promises' => [
            'no-feed' => 'No feed, no notifications, nothing to scroll.',
            'no-streak-guilt' => 'No streak guilt and no loss-anxiety mechanics.',
            'depth-not-time' => 'Progress measures depth understood, never time spent.',
        ],
        'steps' => [
            'name' => 'Name a subject you want to actually understand.',
            'prove' => 'Read one layer, then prove you got it in your own words.',
            'descend' => 'Pass, and the next layer opens beneath it.',
        ],
    ],

    'home' => [
        'kicker' => 'Begin the descent',
        'title' => 'Think deeper',
        'subtitle' => 'Name a subject or paste a link. Each layer asks you to prove you got it before the next one opens — until you surface an expert.',
        'placeholder' => 'Transformers · the French Revolution · https://a-page-you-want-to-understand.com …',
        'placeholder-label' => 'The subject or link you want to understand',
        'composer-hint' => 'One subject, or one link to study — no distractions',
        'cta' => 'Descend',
        'cta-loading' => 'Descending…',
        'resume' => 'Resume',
        'topics-label' => 'Or fall straight into',
        'settings-label' => 'How should it teach?',
        'mode-legend' => 'Choose a learning mode',
        'topics' => [
            'Quantum entanglement',
            'The fall of Rome',
            'How LLMs actually work',
            'Stoicism',
            'The 2008 financial crisis',
        ],
        'how-title' => 'One loop, repeated until you’re expert',
        'how-body' => 'No lesson library, no video queue, no quiz bank. A single conversation with a measurable depth, and a gate at every layer that only opens on evidence.',
        'steps' => [
            'name' => ['title' => 'Name the subject', 'body' => 'Anything you can be curious about — or a link you want to actually understand. The guide starts at the foundation a beginner needs.'],
            'prove' => ['title' => 'Prove the layer', 'body' => 'Explain it back, apply it to a new case, or predict an outcome. Never trivia you could copy.'],
            'descend' => ['title' => 'Descend', 'body' => 'Pass and the next layer opens beneath the last. Miss something and the guide corrects it before you move.'],
        ],
        'features' => [
            'flow' => ['title' => 'Deep-work flow', 'body' => 'One focused thread, no feed, no noise — just you and the subject, going deeper.'],
            'mastery' => ['title' => 'A real mastery map', 'body' => 'Every concept is marked mastered, developing, or misunderstood — from graded evidence, not from clicking “got it”.'],
            'sources' => ['title' => 'Study any page', 'body' => 'Drop in a link and the guide teaches from that material, with checkpoints that hold you to what it actually says.'],
            'library' => ['title' => 'Your own library', 'body' => 'Every subject you’ve been curious about, filed in folders you built, ready to descend again.'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | The library
    |--------------------------------------------------------------------------
    */

    'subjects' => [
        'title' => 'Subjects',
        'new' => 'New descent',
        'select' => 'Select',
        'select-all' => 'Select all',
        'selected' => 'selected',
        'delete' => 'Delete',
        'deleted' => '{0} Nothing was deleted.|{1} One subject deleted.|[2,*] :count subjects deleted.',
        'search-label' => 'Search your subjects',
        'search-placeholder' => 'Search subjects…',
        'filter-label' => 'Filter subjects',
        'filter' => [
            'all' => 'All',
            'active' => 'In progress',
            'surfaced' => 'Surfaced',
            'shared' => 'Shared',
        ],
        'load-more' => 'Load more',
        'empty-title' => 'Nothing open yet',
        'empty-body' => 'Name a subject and the first layer will be waiting. Your progress is saved from the first checkpoint onward.',
        'no-matches' => 'Nothing matches that',
        'no-matches-body' => 'Try a shorter search, or clear the filter.',
        'mastered' => '{1} :count concept mastered|[2,*] :count concepts mastered',
        'status' => [
            'surfaced' => 'Surfaced',
            'checkpoint' => 'Checkpoint open',
            'exploring' => 'In progress',
            'shared' => 'Shared',
        ],

        // Sharing
        'share' => 'Share read-only',
        'unshare' => 'Stop sharing',
        'shared-notice' => 'Anyone with this link can read it: :url',
        'unshared' => 'That link no longer works. The subject is private again.',
        'shared-by' => 'Descended by :name',
        'shared-meta' => 'A read-only descent through :subject — one layer at a time, each one proven.',
        'shared-cta-title' => 'Reading it isn’t understanding it.',
        'shared-cta-body' => 'Start your own descent through this subject, and prove each layer before the next one opens.',

        // Organisation
        'organization-title' => 'Organise',
        'organization-subtitle' => 'Your own shelves. Drag a subject onto a folder, or drop it in the open space to unfile it.',
        'tree-label' => 'Your folders and subjects',
        'tree-search-label' => 'Search folders and subjects',
        'tree-search-placeholder' => 'Filter folders and subjects…',
        'tree-empty-title' => 'No shelves yet',
        'tree-empty-body' => 'Create a folder and drag your subjects in. Folders can hold folders.',
        'tree-note' => 'Deleting a folder deletes the folders inside it and keeps every subject — they go back to unfiled.',
        'unfiled' => 'Unfiled',
        'add-folder' => 'Add',
        'folder-name' => 'Folder name',
        'folder-root' => 'Move to the top level',
        'new-subfolder' => 'New folder inside this one',
        'rename' => 'Rename',
        'save' => 'Save',
        'delete-folder' => 'Delete folder',
        'confirm-delete-folder' => 'Delete folder?',
        'filed' => 'Moved.',
        'folder-created' => 'Folder created.',
        'folder-saved' => 'Folder updated.',
        'folder-deleted' => 'Folder deleted. Its subjects are unfiled.',
        'folder-missing' => 'That folder no longer exists.',
        'folder-cycle' => 'A folder can’t be moved inside itself.',
        'folder-too-deep' => 'That’s as deep as folders go. Move it somewhere higher up.',
    ],

    /*
    |--------------------------------------------------------------------------
    | The boards
    |--------------------------------------------------------------------------
    |
    | Six measures of the same ledger, three windows each. Every label names what
    | was *proven*, because that is the only thing being ranked — there is no
    | board for time spent, and there never will be.
    |
    */

    'rankings' => [
        'title' => 'Rankings',
        'subtitle' => 'Ranked on what you proved, never on how long you stayed.',
        'boards-label' => 'Boards',
        'period-label' => 'Time window',
        'period' => [
            'all' => 'All time',
            'month' => 'This month',
            'week' => 'This week',
        ],

        'board' => [
            'xp' => 'Total XP',
            'xp-hint' => 'Everything earned: layers cleared, concepts mastered, clean first attempts, subjects completed.',
            'xp-unit' => ':count XP',
            'layers' => 'Layers cleared',
            'layers-hint' => 'Every layer that passed a checkpoint, across every subject.',
            'layers-unit' => '{1} :count layer|[2,*] :count layers',
            'concepts' => 'Concepts mastered',
            'concepts-hint' => 'Concepts demonstrated more than once — proven, never asserted.',
            'concepts-unit' => '{1} :count concept|[2,*] :count concepts',
            'depth' => 'Deepest dive',
            'depth-hint' => 'The most layers proven inside one single subject.',
            'depth-unit' => '{1} :count layer|[2,*] :count layers',
            'subjects' => 'Subjects underway',
            'subjects-hint' => 'Subjects with at least one layer cleared. Opening one costs nothing; this counts the ones you got into.',
            'subjects-unit' => '{1} :count subject|[2,*] :count subjects',
            'surfaced' => 'Subjects completed',
            'surfaced-hint' => 'Descents carried all the way to the bottom. The rarest thing here.',
            'surfaced-unit' => '{1} :count subject|[2,*] :count subjects',
        ],

        'rank' => 'Rank',
        'learner' => 'Learner',
        'result' => 'Result',
        'you' => 'You',
        'podium' => 'Top three',
        'participants' => '{0} Nobody on the boards yet|{1} 1 learner on the boards|[2,*] :count learners on the boards',
        'view-profile' => 'Open :name’s record',
        'empty-title' => 'Nothing on this board yet',
        'empty-body' => 'It fills as learners prove layers. Clear one and the first name on it could be yours.',

        'your-standing' => 'Your standing',
        'unranked-value' => 'Nothing here yet',
        'unranked-hint' => 'Clear a layer and you are on this board.',
        'hidden-hint' => 'Only you can see this — you are not on the boards.',
        'gap' => ':value to the next place up',
        'leading' => 'Nobody above you on this board.',
        'best-standings' => 'Best standings',
        'no-standings' => 'Not on any board yet.',

        'join-title' => 'Stand on the boards',
        'join-body' => 'Joining publishes your name, your picture and your learning record to other learners. Nothing else changes, and you can leave whenever you like.',
        'join-cta' => 'Join the rankings',
        'leave-title' => 'You are on the boards',
        'leave-body' => 'Other learners can see your name and your learning record, and open your profile from any ranking.',
        'leave-cta' => 'Leave the rankings',
        'joined' => 'You are on the boards. Your record is visible to other learners.',
        'left' => 'You have left the boards. Your record is private again.',
    ],

    /*
    |--------------------------------------------------------------------------
    | One learner, as other learners see them
    |--------------------------------------------------------------------------
    */

    'learners' => [
        'title' => ':name’s record',
        'meta' => 'What :name has proven on Down the Rabbit Hole — layers cleared, concepts mastered, subjects completed.',
        'since' => 'Descending since :date',
        'preview-title' => 'Only you can see this page',
        'preview-body' => 'This is exactly what other learners would see if you joined the boards.',
        'shared-title' => 'Published descents',
        'shared-body' => 'Subjects :name chose to make readable.',
        'shared-empty' => ':name has not published a descent yet.',
        'back-to-rankings' => 'Back to the rankings',
    ],

    'profile' => [
        'title' => 'Your account',
        'tabs-label' => 'Account sections',
        'tab-record' => 'Record',
        'tab-profile' => 'Details',
        'tab-password' => 'Password',
        'change-photo' => 'Change photo',
        'xp' => ':xp XP',
        'details' => 'Your details',
        'details-hint' => 'Used on your account and in the emails we send you.',
        'phone' => 'Phone number',
        'email-change-hint' => 'Changing this address means confirming the new one.',
        'save' => 'Save changes',
        'change-password' => 'Change password',
        'change-password-hint' => 'Use a strong password you don’t use anywhere else.',
        'current-password' => 'Current password',
        'new-password' => 'New password',
        'update-password' => 'Update password',
        'updated' => 'Your details have been saved.',
        'password-updated' => 'Your password has been changed.',
        'password-incorrect' => 'Your current password is incorrect.',
        'avatar-updated' => 'Your photo has been updated.',
        'error' => 'Something went wrong. Please try again.',
        'record' => [
            'layers' => 'Layers cleared',
            'layers-hint' => 'Each one passed a checkpoint.',
            'mastered' => 'Concepts mastered',
            'mastered-hint' => 'Demonstrated more than once.',
            'deepest' => 'Deepest dive',
            'deepest-hint' => 'Layers proven inside one subject.',
            'deepest-dive' => 'Your deepest dive',
            'deepest-dive-value' => '{1} :subject — 1 layer|[2,*] :subject — :count layers',
            'subjects' => 'Subjects',
            'surfaced' => 'Completed',
            'to-review' => 'To revisit',
            'to-review-hint' => 'Concepts still misunderstood.',
            'note' => 'These are the only numbers we keep. There is no time-on-site total, and nothing here rewards opening the app without learning something. The rankings measure these same numbers, and only if you ask them to.',
        ],
    ],

    'chat' => [
        // Flash / error messages
        'no-access' => 'That subject isn’t yours to explore.',
        'no-checkpoint' => 'There’s no open checkpoint on this layer.',
        'daily-limit' => 'You’ve reached today’s descent limit. Come back tomorrow — rest is part of deep work.',
        'already-surfaced' => 'You’ve already surfaced from this one.',
        'llm-error' => 'The guide lost the thread for a moment. Give it another go.',
        'grade-unavailable' => 'The guide couldn’t read your answer just now — nothing is lost, try submitting it again.',
        'connection-lost' => 'The connection dropped. Reload to continue where you were.',

        // Studying a page
        'source-unreachable' => 'That link can’t be reached from here. Check it, or name the subject instead.',
        'source-failed' => 'That page wouldn’t load. Try another link, or name the subject instead.',
        'source-too-thin' => 'There isn’t enough readable text on that page to descend through.',

        // The subject
        'subject-label' => 'Descending into',
        'depth-reached' => 'Depth reached: Layer :depth of :max',
        'layer' => 'Layer',
        'depth-rail' => 'The descent',
        'mastery' => 'Mastery',
        'concepts' => 'Concepts',
        'concepts-empty' => 'Concepts appear here as you prove them, marked by what you’ve actually demonstrated.',
        'thinking' => 'Thinking',
        'thought-for' => 'Thought it through',
        'analyzing' => 'Reading your answer against the layer…',
        'begin' => 'Open the first layer',
        // SQ3R's Survey step, for a subject grounded in a page. Named after
        // the technique on purpose: the entry at /methods/sq3r is one link
        // away, and a step named after its method explains itself.
        'survey' => 'Survey',
        'survey-cta' => 'Survey the page first',
        'survey-hint' => 'A map of the ground before you go into it. It costs no depth and proves nothing.',
        'go-deeper' => 'Go deeper',
        'next-layer' => 'Layer :depth opens beneath this one',
        'focus-toggle' => 'Deep-work mode',
        'focus-on' => 'Deep-work mode on',
        'focus-off' => 'Deep-work mode off',
        'stop' => 'Stop',
        'stopped' => 'Stopped. What arrived is kept.',
        'copy' => 'Copy this layer',
        'copied' => 'Copied',

        // What the guide is doing, before there is prose to show
        'stage' => [
            'orienting' => 'Orienting in “:subject”',
            'reading' => 'Reading :title — :words words',
            'recalling' => '{1} Recalling 1 concept you’ve proven|[2,*] Recalling :count concepts you’ve proven',
            'revisiting' => '{1} Weaving back 1 concept to revisit|[2,*] Weaving back :count concepts to revisit',
            'composing-teach' => 'Composing Layer :layer',
            'composing-question' => 'Setting the checkpoint for Layer :layer',
            'composing-survey' => 'Mapping the ground',
            'writing' => 'Writing it out',
        ],

        // Checkpoint
        'checkpoint' => 'Prove you’ve got it',
        'checkpoint-ready' => 'A checkpoint is waiting for your answer.',
        'proof-placeholder' => 'Explain it back in your own words…',
        'submit-proof' => 'Prove it',
        'your-answer' => 'Your answer',
        'their-answer' => 'Their answer',
        'confidence-legend' => 'How sure are you?',
        'confidence' => [
            'shaky' => 'Shaky',
            'mostly' => 'Mostly there',
            'solid' => 'Solid',
        ],
        'stuck' => 'Stuck? Think it through another way',
        'reframe' => [
            'different' => 'Explain differently',
            'analogy' => 'Give an analogy',
            'evidence' => 'Show the evidence',
            'challenge' => 'Challenge me',
        ],

        // Verdict
        'verdict-pass' => 'Layer cleared',
        'verdict-incomplete' => 'Not proven yet',
        'verdict-misconception' => 'Something to untangle first',
        'verdict-toggle' => 'Show or hide verdict details',
        'score' => 'Score',
        'criterion-met' => 'shown',
        'criterion-unmet' => 'not shown yet',
        'you-thought' => 'You seemed to think:',
        'calibration-good' => 'Well calibrated — you knew what you knew',
        'calibration-over' => 'You felt surer than the answer showed — worth a second look',
        'calibration-under' => 'You knew more than you gave yourself credit for',
        'resurfaced' => 'Resurfaced from depth :depth',
        'resurfaced-short' => 'from :depth',

        // States
        'layer-state' => [
            'cleared' => 'cleared',
            'current' => 'current layer',
            'review' => 'cleared, something to revisit',
            'locked' => 'not yet open',
        ],
        'concept-state' => [
            'mastered' => 'Mastered',
            'developing' => 'Developing',
            'misunderstood' => 'Misunderstood',
            'unexplored' => 'Not explored',
        ],

        // Surfaced
        'surfaced-title' => 'You’ve surfaced an expert.',
        'surfaced-body' => 'You went all the way down and came back up, proving every layer on the way. That’s the whole game.',
        'new-descent' => 'Start another descent',
    ],

    /*
    |--------------------------------------------------------------------------
    | The methods
    |--------------------------------------------------------------------------
    |
    | A short reference desk for the techniques the product is built on. The body
    | of each entry is written by the guide, once per language, and stored — the
    | reading lists underneath are hand-checked and live in config/platform.php,
    | because a model must never be the source of a citation.
    |
    */

    'methods' => [
        'title' => 'The methods',
        'subtitle' => 'Every mechanic here comes from somewhere. This is what each technique is, when it works, where the evidence is thin — and where you meet it in the app.',
        'meta' => 'The learning techniques behind Down the Rabbit Hole — what each one is, when it works, and the research behind it.',
        'entry-meta' => ':name — what it is, when it works, and the evidence behind it.',
        'link' => 'What is :name?',
        'back' => 'All methods',
        'read' => 'Read the entry',
        'references' => 'Read further',
        'references-note' => 'Hand-checked sources. The entry above is written for you in your language; this list is not.',
        'unavailable' => 'This entry is still being written. Come back in a moment — the reading list below is already here.',
        'cta-title' => 'Reading about a method is not using one.',
        'cta-body' => 'Name a subject and the loop starts: one layer, one checkpoint, one thing proved.',
        'cta' => 'Start a descent',

        // Prompt input, not UI copy: the product's own vocabulary, handed to
        // the guide so an entry describing the app uses the same words the app
        // does. A new language supplies its own mapping here or the entry will
        // invent one — which is how "coborâre" first came back as "descentrare".
        'glossary' => 'subject, layer, descent, checkpoint, prove it, mastery, surfaced, guide, learner',

        'items' => [
            'sq3r' => [
                'name' => 'SQ3R',
                'summary' => 'Survey, Question, Read, Recite, Review — five steps that turn reading a text into answering it.',
            ],
            'retrieval-practice' => [
                'name' => 'Retrieval practice',
                'summary' => 'Pulling an idea back out of memory instead of putting it in again. The effort of recalling is what makes it hold.',
            ],
            'spaced-repetition' => [
                'name' => 'Spaced practice',
                'summary' => 'The same hours, spread out rather than massed. Forgetting a little in between is the mechanism, not the failure.',
            ],
            'desirable-difficulties' => [
                'name' => 'Desirable difficulties',
                'summary' => 'Conditions that slow learning down as it happens and improve what survives. Easy study feels better and works worse.',
            ],
            'self-explanation' => [
                'name' => 'Self-explanation',
                'summary' => 'Saying why to yourself — why this step follows, why that answer is wrong. It exposes the gaps a re-read hides.',
            ],
            'interleaving' => [
                'name' => 'Interleaving',
                'summary' => 'Mixing related problems instead of drilling one kind at a time, so you have to choose the approach and not just run it.',
            ],
            'calibration' => [
                'name' => 'Calibration',
                'summary' => 'Knowing what you know. The gap between how sure you felt and how right you were is trainable — and it is what tells you when to stop studying.',
            ],
            'learning-by-teaching' => [
                'name' => 'Learning by teaching',
                'summary' => 'Explaining an idea in plain words to someone who does not have it. The parts you cannot say are exactly the parts you do not have.',
            ],
            'socratic' => [
                'name' => 'The Socratic method',
                'summary' => 'Being asked rather than told, one question at a time, until the answer is yours instead of remembered.',
            ],
        ],
    ],
];
