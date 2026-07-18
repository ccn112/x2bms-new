<?php

/*
| X2AI chat — provider, model, cost-control. Design ported from xweb chat module
| (xweb/docs/CHAT_MODULE_HANDOFF.md) but storage uses X2's own Ai* tables.
| Key ONLY on the backend; client is a dumb pipe. Provider swappable via env.
*/
return [

    // anthropic | openai | gemini | fake   (fake = local echo stream, no key/cost)
    'provider' => env('CHAT_PROVIDER', 'anthropic'),

    'providers' => [
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'), // rẻ nhất, hợp cư dân/BQL
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
            'version' => '2023-06-01',
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],
    ],

    // Cost-control knobs (xweb-proven defaults). All env-overridable.
    'limits' => [
        'max_input_chars' => (int) env('CHAT_MAX_INPUT_CHARS', 4000),
        'max_history_messages' => (int) env('CHAT_MAX_HISTORY_MESSAGES', 14),
        'max_output_tokens' => (int) env('CHAT_MAX_OUTPUT_TOKENS', 1024),
        'rate_per_minute' => (int) env('CHAT_RATE_LIMIT_PER_MINUTE', 20),
        'anon_daily_max' => (int) env('CHAT_ANON_DAILY_MAX', 12),
        'auth_daily_max' => (int) env('CHAT_AUTH_DAILY_MAX', 60),
    ],

    // $/1M tokens — estimate only (for AiUsageLog.cost), reconcile with provider dashboard.
    'prices' => [
        'claude-haiku-4-5' => ['in' => 1.00, 'out' => 5.00],
        'gpt-4o-mini' => ['in' => 0.15, 'out' => 0.60],
    ],

    // Fallback guardrail if no AiPromptTemplate/AiGuardrailPolicy is configured in DB.
    'default_system_prompt' => <<<'PROMPT'
        Bạn là X2AI — trợ lý trong ứng dụng quản lý toà nhà X2-BMS. Trả lời ngắn gọn, lịch sự bằng tiếng Việt.
        Chỉ hỗ trợ các chủ đề: cư dân, căn hộ, phí/hoá đơn, phản ánh, tiện ích, thông báo, quy định toà nhà.
        Từ chối lịch sự các câu hỏi ngoài phạm vi. KHÔNG bịa thông tin nội bộ; nếu không chắc, hướng dẫn liên hệ Ban quản lý.
        KHÔNG tiết lộ dữ liệu cá nhân (CCCD, số điện thoại đầy đủ, công nợ người khác). Không dùng bảng markdown.
        PROMPT,

    // Advanced features that require a logged-in identity (app pushes anon users to the Action Gate).
    'anonymous_allowed_actions' => ['chat'],   // basic Q&A only
    'auth_required_actions' => ['lookup', 'action', 'draft', 'analyze'],
];
