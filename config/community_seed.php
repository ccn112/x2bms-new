<?php

declare(strict_types=1);

/**
 * Cấu hình bộ sinh dữ liệu cộng đồng quy mô lớn (`community:seed-scale`).
 *
 * Bốn profile theo gói handoff X2_BMS_COMMUNITY_MASS_SEED. Chỉ `demo`/`ux` nên
 * chạy trên DB dev; `load`/`full` để dành staging (xem docblock của command).
 *
 * `comments_distribution`:
 *  - uniform: số bình luận/bài rải đều trong [min,max].
 *  - zipf   : đa số bài ít bình luận, số ít bài rất nhiều (giống feed thật).
 */
return [
    'profiles' => [
        'demo' => [
            'posts' => 2_000,
            'comments_min' => 3,
            'comments_max' => 12,
            'comments_distribution' => 'uniform',
            'reactions_ratio' => 0.65,
            'viral_ratio' => 0.02,
            'batch_size' => 1_000,
        ],
        'ux' => [
            'posts' => 50_000,
            'comments_min' => 10,
            'comments_max' => 30,
            'comments_distribution' => 'uniform',
            'reactions_ratio' => 0.72,
            'viral_ratio' => 0.03,
            'batch_size' => 2_000,
        ],
        'load' => [
            'posts' => 1_000_000,
            'comments_min' => 0,
            'comments_max' => 30,
            'comments_distribution' => 'zipf',
            'reactions_ratio' => 0.68,
            'viral_ratio' => 0.04,
            'batch_size' => 5_000,
        ],
        'full' => [
            'posts' => 1_000_000,
            'comments_min' => 20,
            'comments_max' => 30,
            'comments_distribution' => 'uniform',
            'reactions_ratio' => 0.75,
            'viral_ratio' => 0.05,
            'batch_size' => 5_000,
        ],
    ],

    // Seed cố định → dữ liệu deterministic, test khẳng định được số liệu.
    'seed' => (int) env('COMMUNITY_SEED_RANDOM_SEED', 20260726),

    // Ngân hàng nội dung tiếng Việt (posts.vi.json, comments.vi.json, entities.vi.json).
    'content_path' => database_path('seed-data/community'),

    // Nhãn đánh dấu bản ghi do bộ seed sinh ra. `--reset` CHỈ xoá bài mang nhãn
    // này → không đụng tới dữ liệu demo/thật (bài người dùng, CommunityFeedDemoSeeder).
    'seed_tag' => 'mass',
];
