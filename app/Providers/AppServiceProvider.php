<?php

namespace App\Providers;

use App\Contracts\LlmClient;
use App\Services\Chat\SubjectFolderService;
use App\Services\Chat\SubjectLibraryService;
use App\Services\Llm\AnthropicClient;
use App\Services\Llm\GeminiClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The single seam over the LLM provider — CHAT_PROVIDER picks the client.
        $this->app->bind(LlmClient::class, fn ($app) => match (config('platform.chat.provider')) {
            'anthropic' => $app->make(AnthropicClient::class),
            default => $app->make(GeminiClient::class),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiters();
        $this->composeSidebar();

        // LayerCompleted -> AwardLayerRewards is auto-discovered from app/Listeners
        // (Laravel 11+ scans the handle() type-hint), so no manual wiring here.
    }

    /**
     * The sidebar is on every app screen, so its data comes from a composer
     * rather than from every controller passing the same two things down.
     *
     * Which view is showing is a client preference, so it travels as a cookie —
     * that way the server builds the folder tree only when the folder tree is
     * what's on screen, and the sidebar never paints the wrong view first.
     */
    private function composeSidebar(): void
    {
        View::composer('components.frontend.sidebar', function (ViewInstance $view) {
            $userId = auth()->id();
            $folderView = request()->cookie('dth_subject_view') === 'folders';

            $view->with([
                'folderView' => $folderView,
                'recents' => $userId && ! $folderView
                    ? app(SubjectLibraryService::class)->recent($userId)
                    : new Collection,
                'tree' => $userId && $folderView
                    ? app(SubjectFolderService::class)->tree($userId)
                    : ['folders' => new Collection, 'unfiled' => new Collection],
            ]);
        });
    }

    /**
     * Named limiters for the auth surface. Login is keyed per email + IP, so a
     * single account can't be brute-forced and one IP can't spray many accounts.
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('oauth', fn (Request $request) => Limit::perMinute(15)->by($request->ip()));

        // Opening a subject is the one unauthenticated action that costs money —
        // and with a URL in the composer it also costs an outbound fetch.
        RateLimiter::for('descend', fn (Request $request) => Limit::perMinute(10)
            ->by($request->user()?->id ?: $request->ip()));
    }
}
