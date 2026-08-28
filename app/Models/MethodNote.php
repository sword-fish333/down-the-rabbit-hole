<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One method entry, written once per language and kept.
 *
 * Deliberately dumb: the registry in config('platform.methods') says which
 * methods exist and carries their reading lists, so this table holds nothing
 * but prose. Delete a row and the next visitor regenerates it.
 */
class MethodNote extends Model
{
    protected $fillable = ['slug', 'locale', 'body', 'model'];
}
