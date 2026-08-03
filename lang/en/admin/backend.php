<?php

return [

    'auth' => [
        'login-successful' => 'Welcome back! You are now signed in.',
        'logout-successful' => 'You have been signed out.',
        'invalid-credentials' => 'The email or password you entered is incorrect.',
        'account-blocked' => 'Your account has been disabled. Please contact an administrator.',
        'current-password-incorrect' => 'Your current password is incorrect.',
        'no-account-available' => 'No admin account is linked to that email address.',
        'google-auth-failed' => 'We could not sign you in with Google. Please try again.',
        'google-socialite-error' => 'Google sign-in error: :error',
    ],

    'profile' => [
        'profile-updated-successfully' => 'Your profile has been updated.',
        'password-updated-successfully' => 'Your password has been changed.',
        'profile-image-updated-successfully' => 'Your profile photo has been updated.',
        'support-request-made-successfully' => 'Your support request has been sent. Our team will get back to you shortly.',
        'an-error-occurred' => 'Something went wrong. Please try again.',
    ],

    'learning-modes' => [
        'created' => 'The learning mode has been created.',
        'updated' => 'The learning mode has been saved.',
        'deleted' => 'The learning mode has been deleted.',
        'in-use' => 'This mode is used by existing subjects. Disable it instead — that hides it from new descents without breaking the ones already running.',
        'cannot-disable-default' => 'This is the default mode for new descents. Promote another mode to default first.',
    ],

    'users' => [
        'updated' => 'The learner’s account has been saved.',
        'deleted' => 'The learner and their descents have been deleted.',
    ],

    'conversations' => [
        'deleted' => 'The subject has been deleted.',
    ],

    'mail' => [
        'support-subject' => 'Support request: :title',
        'support-heading' => 'New support request',
        'thanks' => 'Thanks,',
    ],
];
