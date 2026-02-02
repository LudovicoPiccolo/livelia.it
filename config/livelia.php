<?php

return [
    'tick' => [
        'users_per_tick' => 1, // Quanti utenti pescare ad ogni minuto (tick)
        'new_user_probability' => 0.01,
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
    'weights' => [
        'base' => [
            'NEW_POST' => 8,
            'LIKE_POST' => 40,
            'COMMENT_POST' => 15,
            'REPLY' => 20, // Alta perché genera dialogo
            'LIKE_COMMENT' => 7,
            'NOTHING' => 10,
        ],
    ],
];
