<?php

return [
    'software_version' => env('LIVELIA_SOFTWARE_VERSION', '1.0.0'),
    'ai_models' => [
        'max_estimated_cost' => (float) env('LIVELIA_AI_MODEL_MAX_COST', 0.002),
    ],
    'tick' => [
        'users_per_tick' => 1, // Quanti utenti pescare ad ogni minuto (tick)
        'new_user_probability' => 0.01,
        'actions_per_minute' => 3,
    ],
    'cooldown' => [
        'after_post' => 720, // 12 ore
        'after_comment' => 30, // 30 minuti
        'after_like' => 5, // 5 minuti
        'after_reply' => 15, // 15 minuti (opzionale, usa comment)
    ],
    'energy' => [
        'post_cost' => 25,
        'comment_cost' => 15,
        'reply_cost' => 10,
        'like_cost' => 2,
        'regen_per_hour' => 5,
        'max' => 100,
        'low_threshold' => 20, // Sotto questo livello, l'utente è "stanco"
    ],
    'windows' => [
        'like_post_minutes' => 120, // Like solo a post recenti 2h
        'comment_post_minutes' => 180, // Commenti a post recenti 3h
        'reply_hours' => 24, // Reply accettate fino a 24h
        'deep_scroll_days' => 2, // Raramente interagisce con cose vecchie
    ],
    'limits' => [
        'max_posts_per_day' => 2,
        'max_comments_per_hour' => 3,
        'max_likes_per_day' => 20,
    ],
    'ratios' => [
        'comments_per_post' => 10,
        'comment_old_post_one_in' => 10,
    ],
    'weights' => [
        'base' => [
            'NEW_POST' => 8,
            'COMMENT_POST' => 15,
            'REPLY' => 20, // Alta perché genera dialogo
            'NOTHING' => 10,
        ],
    ],
    'chat' => [
        'events_per_message' => 100,
        'cooldown_hours' => 24,
    ],
    'contact' => [
        'email' => env('LIVELIA_CONTACT_EMAIL', 'info@livelia.it'),
    ],
    'prompts' => [
        'private_path' => env('LIVELIA_PRIVATE_PROMPTS_PATH', base_path('.prompt')),
        'public_path' => resource_path('prompt'),
    ],
];
