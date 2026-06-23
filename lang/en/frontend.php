<?php

return [
    'navbar' => [
        'theme-toggle' => 'Toggle light / dark',
        'sign-in'      => 'Sign in',
        'start'        => 'Start a descent',
        'sign-out'     => 'Sign out',
    ],

    'auth' => [
        'register-title'    => 'Create your account',
        'register-subtitle' => 'Save your descents, earn each layer, keep your streak alive.',
        'login-title'       => 'Welcome back',
        'login-subtitle'    => 'Pick up where you left off.',
        'name'              => 'Name',
        'email'             => 'Email',
        'password'          => 'Password',
        'password-confirm'  => 'Confirm password',
        'remember'          => 'Keep me signed in',
        'register-cta'      => 'Create account',
        'login-cta'         => 'Sign in',
        'have-account'      => 'Already have an account?',
        'no-account'        => 'New here?',
        'go-login'          => 'Sign in',
        'go-register'       => 'Create one',
        'welcome'           => 'Welcome to the rabbit hole.',
        'welcome-back'      => 'Welcome back.',
        'signed-out'        => 'Signed out.',
        'invalid-credentials' => 'Those credentials don’t match our records.',
        'account-blocked'   => 'This account has been disabled.',

        // Brand showcase (desktop split panel)
        'brand-headline'    => 'Fall in. Surface an expert.',
        'brand-subline'     => 'A focused descent through any subject — prove each layer to unlock the next.',
        'feature-depth'     => 'Go deeper, one layer at a time',
        'feature-prove'     => 'Prove it before you progress',
        'feature-streak'    => 'Keep your daily streak alive',

        // OAuth + fields
        'or'                   => 'or',
        'continue-with-google' => 'Continue with Google',
        'toggle-password'      => 'Show or hide password',
        'google-failed'        => 'Google sign-in didn’t complete. Please try again.',
        'google-error'         => 'Google OAuth error (learner guard): :error',
        'name-placeholder'     => 'What should we call you?',
        'email-placeholder'    => 'you@example.com',
        'password-placeholder' => '••••••••',
    ],

    'footer' => [
        'tagline' => 'Learn anything, all the way down.',
        'rights'  => 'All rights reserved.',
    ],

    'home' => [
        'kicker'      => 'Begin the descent',
        'title'       => 'What do you want to understand?',
        'subtitle'    => 'Name a subject. Fall in. Each layer asks you to prove you got it before the next one opens — until you surface an expert.',
        'placeholder' => 'World War II · transformers · the French Revolution · how interest rates work…',
        'cta'         => 'Descend',
        'topics-label'=> 'Or fall straight into',
        'topics'      => [
            'Quantum entanglement',
            'The fall of Rome',
            'How LLMs actually work',
            'Stoicism',
            'The 2008 financial crisis',
        ],
        'features' => [
            'flow'  => ['title' => 'Deep-work flow', 'body' => 'One focused thread, no feed, no noise — just you and the subject, going deeper.'],
            'prove' => ['title' => 'Earn each layer', 'body' => 'Answer a short set of questions on what you just learned to unlock the next descent.'],
            'real'  => ['title' => 'Real sources', 'body' => 'The right books, papers and articles surface as you go — matched to where you are.'],
        ],
    ],

    'chat' => [
        // Flash / error messages
        'no-access'         => 'That rabbit hole isn’t yours to explore.',
        'daily-limit'       => 'You’ve reached today’s descent limit. Come back tomorrow — rest is part of deep work.',
        'already-surfaced'  => 'You’ve already surfaced from this one.',
        'llm-error'         => 'The guide lost the thread for a moment. Give it another go.',

        // The hole
        'subject-label'     => 'Descending into',
        'depth'             => 'Depth',
        'of'                => 'of',
        'thinking'          => 'The guide is thinking…',
        'checkpoint'        => 'Prove you’ve got it',
        'proof-placeholder' => 'Explain it back in your own words…',
        'submit-proof'      => 'Submit answer',
        'go-deeper'         => 'Go deeper',
        'layer-cleared'     => 'Layer cleared — the next one just opened.',
        'surfaced-title'    => 'You’ve surfaced an expert.',
        'surfaced-body'     => 'You went all the way down and came back up. That’s the whole game.',
        'new-descent'       => 'Start another descent',
    ],
];
