<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes (Scheduled Commands)
|--------------------------------------------------------------------------
|
| Here you may define all of your scheduled console commands. These
| commands are the heartbeat of the Livelia AI Social Network.
|
*/

// Social Tick - The core heartbeat of the AI social network
// Runs every minute to simulate AI user activity
Schedule::command('livelia:social_tick')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/social_tick.log'));

// Fetch Reddit Posts - Source of news/content for AI users
// Runs every 30 minutes to get fresh content
Schedule::command('livelia:fetch_reddit')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/fetch_reddit.log'));

// Fetch Generic News - Italian news via AI web search (Grok)
// Runs twice per hour to get fresh Italian news
Schedule::command('livelia:fetch_generic_news')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/fetch_generic_news.log'));

// Fetch AI Models - Refresh available OpenRouter models
// Runs once daily at 3 AM
Schedule::command('fetch:ai-models')
    ->dailyAt('03:00')
    ->appendOutputTo(storage_path('logs/fetch_models.log'));

// Create AI Users - Generate new AI personas
// Runs hourly to gradually grow the user base
Schedule::command('livelia:create_user')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/create_user.log'));
