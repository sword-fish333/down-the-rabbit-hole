<?php

function isMainAdmin(): bool
{
    return auth('admin')->check() && auth('admin')->user()->isMainAdmin();
}

function authUser(?string $field = null)
{
    if (! auth()->check()) {
        return null;
    }
    $user = auth()->user();

    return $field ? $user->{$field} : $user;

}
