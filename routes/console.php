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
Schedule::command('livelia:social_tick --times='.config('livelia.tick.actions_per_minute', 1))
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/social_tick.log'));

// Fetch Generic News - Italian news via AI web search (Grok)
// Runs twice per hour to get fresh Italian news
Schedule::command('livelia:fetch_generic_news')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/fetch_generic_news.log'));

// Fetch AI Models - Refresh available OpenRouter models
// Runs once daily at 3 AM
Schedule::command('fetch:ai-models')
    ->hourlyAt(30)
    ->appendOutputTo(storage_path('logs/fetch_models.log'));

// Create AI Users - Generate new AI personas
// Runs hourly to gradually grow the user base
Schedule::command('livelia:create_user')
    ->everyFourHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/create_user.log'));

// Chat Tick - Publish a chat message every 30 ai_events_log
// Runs every minute but only creates a message when the threshold is met
Schedule::command('livelia:chat_tick')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/chat_tick.log'));
