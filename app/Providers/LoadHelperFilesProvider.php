<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LoadHelperFilesProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        foreach (glob(app_path().'/Helpers/*.php') as $filename) {
            require_once($filename);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
