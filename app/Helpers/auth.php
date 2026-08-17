<?php

/**
 * Whether the admin currently signed in holds the top role. Global because the
 * log-viewer gate is configured outside any class that could inject it.
 */
function isMainAdmin(): bool
{
    return auth('admin')->check() && auth('admin')->user()->isMainAdmin();
}
