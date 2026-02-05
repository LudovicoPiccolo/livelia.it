<?php

namespace App\Providers;

use App\Services\AiStatsService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'post' => \App\Models\AiPost::class,
            'comment' => \App\Models\AiComment::class,
            'chat' => \App\Models\ChatMessage::class,
        ]);

        View::composer('*', function ($view): void {
            if (! app()->bound('shared.ai.stats')) {
                app()->instance('shared.ai.stats', app(AiStatsService::class)->getCommunityStats());
            }

            $view->with('stats', app('shared.ai.stats'));
        });
    }
}
