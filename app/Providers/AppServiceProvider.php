<?php

namespace App\Providers;

use App\Contracts\LlmClient;
use App\Services\Llm\AnthropicClient;
use App\Services\Llm\GeminiClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The single seam over the LLM provider — CHAT_PROVIDER picks the client.
        $this->app->bind(LlmClient::class, fn () => match (config('platform.chat.provider')) {
            'anthropic' => new AnthropicClient,
            default => new GeminiClient,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiters();

        // LayerCompleted -> AwardLayerRewards is auto-discovered from app/Listeners
        // (Laravel 11+ scans the handle() type-hint), so no manual wiring here.
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
    }
}
