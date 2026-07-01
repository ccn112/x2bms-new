<?php

namespace App\Livewire;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiUsageLog;
use App\Support\Context\CurrentContext;
use App\Support\X2AI\X2aiClient;
use App\Support\X2AI\X2aiPolicyGate;
use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * WEB-UX-09 — X2AI Copilot chat (embedded in the global floating FAB).
 *
 * Mode is permission-driven (X2aiPolicyGate), not a user toggle:
 *   - 'context' : reads the current screen + the user's accessible screens; can
 *                 attach images/PDFs for the model to read.
 *   - 'data'    : (ai.data_lookup permission) also enables the lookup_data tool.
 * Assistant replies are rendered as Markdown (tables/headings/lists → styled HTML).
 */
class X2aiChat extends Component
{
    use WithFileUploads;

    /** @var array<int, array{role:string, content:string, html?:string}> */
    public array $messages = [];

    public string $input = '';

    /** context | data — resolved from permissions in mount()/submit(), no UI toggle. */
    public string $mode = 'context';

    public string $greeting = 'Xin chào! Tôi là X2AI. Tôi có thể giúp gì cho công việc vận hành hôm nay?';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $attachments = [];

    public ?array $pageContext = null;

    /** Live text of the rendered screen, captured from the DOM on the client at send time. */
    public ?string $screenText = null;

    /** Two-step send (ChatGPT-style): submit() shows the prompt instantly; generate() awaits the reply. */
    public bool $awaitingReply = false;

    public string $pendingText = '';

    public ?string $pendingScreenText = null;

    /** Current conversation; null until the first message creates a session. */
    public ?int $sessionId = null;

    /** History panel state + the user's past sessions (loaded on demand). */
    public bool $showHistory = false;

    /** @var array<int, array{id:int, title:string, surface:?string, time:?string}> */
    public array $sessions = [];

    public function mount(?array $pageContext = null, ?string $greeting = null): void
    {
        $this->pageContext = $pageContext;
        if ($greeting) {
            $this->greeting = $greeting;
        }
        $this->mode = app(X2aiPolicyGate::class)->effectiveMode(auth()->user());
        // Start fresh each page load — past conversations live behind the history icon.
    }

    /** Toggle the history panel; load the session list when opening it. */
    #[On('x2ai-history')]
    public function toggleHistory(): void
    {
        $this->showHistory = ! $this->showHistory;
        if ($this->showHistory) {
            $this->loadSessions();
        }
    }

    /** Start a brand-new conversation (each screen / pre-prompt is its own session). */
    #[On('x2ai-new-chat')]
    public function newChat(): void
    {
        $this->sessionId = null;
        $this->messages = [];
        $this->showHistory = false;
        $this->reset('input', 'attachments');
    }

    /** Reopen a past session and load its turns into the chat. */
    public function loadSession(int $id): void
    {
        $session = AiChatSession::where('user_id', auth()->id())->find($id);
        if (! $session) {
            return;
        }

        $this->sessionId = $session->id;
        $this->messages = AiChatMessage::where('ai_chat_session_id', $session->id)
            ->orderBy('id')
            ->get()
            ->map(fn (AiChatMessage $m) => $m->role === 'assistant'
                ? ['role' => 'assistant', 'content' => $m->content, 'html' => $this->toHtml($m->content)]
                : ['role' => 'user', 'content' => $m->content])
            ->all();
        $this->showHistory = false;
        $this->dispatch('x2ai-scroll');
    }

    /** Load the account's recent sessions for the history panel. */
    private function loadSessions(): void
    {
        if (! $userId = auth()->id()) {
            $this->sessions = [];

            return;
        }

        $this->sessions = AiChatSession::where('user_id', $userId)
            ->whereNotNull('last_message_at')
            ->orderByDesc('last_message_at')
            ->limit(50)
            ->get()
            ->map(fn (AiChatSession $s) => [
                'id' => $s->id,
                'title' => $s->title ?: 'Cuộc trò chuyện',
                'surface' => $s->surface,
                'time' => optional($s->last_message_at)->format('d/m H:i'),
            ])
            ->all();
    }

    /** Create the session lazily on the first message of a conversation. */
    private function ensureSession(string $firstText): void
    {
        if ($this->sessionId || ! $user = auth()->user()) {
            return;
        }

        try {
            $session = AiChatSession::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'title' => Str::limit(trim($firstText), 60) ?: 'Cuộc trò chuyện',
                'surface' => $this->surfaceLabel(),
                'last_message_at' => now(),
            ]);
            $this->sessionId = $session->id;
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Persist one chat turn into the current session (best-effort — never breaks the chat). */
    private function saveMessage(string $role, string $content): void
    {
        try {
            if (! $user = auth()->user()) {
                return;
            }
            AiChatMessage::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'ai_chat_session_id' => $this->sessionId,
                'role' => $role,
                'content' => $content,
            ]);
            if ($this->sessionId) {
                AiChatSession::whereKey($this->sessionId)->update(['last_message_at' => now()]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Prefill the input from a "Gợi ý nhanh" / Support Copilot button on any screen. */
    #[On('x2ai-prefill')]
    public function prefill(string $prompt = ''): void
    {
        $this->input = $prompt;
    }

    /**
     * Step 1 (fast): show the user's prompt as a chat bubble immediately and arm
     * the reply. The model call happens in generate() on a second request, so the
     * UI shows the prompt + "thinking" without waiting for the API.
     */
    public function submit(?string $screenText = null): void
    {
        $text = trim($this->input);
        if ($this->awaitingReply || ($text === '' && empty($this->attachments))) {
            return;
        }

        $this->validate([
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,webp,pdf',
        ]);

        // Capture the screen now so the session's surface label reflects the right page.
        $this->pendingScreenText = $screenText ? mb_substr($screenText, 0, 6000) : null;
        $this->screenText = $this->pendingScreenText;

        // Display the user turn now (text + attachment note).
        $note = $this->attachments ? ' 📎 '.count($this->attachments).' tệp' : '';
        $display = ($text !== '' ? $text : '(đính kèm)').$note;
        $this->messages[] = ['role' => 'user', 'content' => $display];
        $this->ensureSession($text !== '' ? $text : 'Đính kèm tệp');
        $this->saveMessage('user', $display);

        // Stash what generate() needs; keep attachments until the reply is built.
        $this->pendingText = $text;
        $this->awaitingReply = true;
        $this->reset('input');
        $this->dispatch('x2ai-scroll');
    }

    /** Step 2: armed by x-init when awaitingReply — calls the model and appends the reply. */
    public function generate(): void
    {
        if (! $this->awaitingReply) {
            return;
        }
        $this->awaitingReply = false;

        $text = $this->pendingText;
        $this->screenText = $this->pendingScreenText;

        $gate = app(X2aiPolicyGate::class);
        $user = auth()->user();

        // --- Governance gate (WEB-UX-09 / CLAUDE.md: permission + risk + approval) ---
        if (! $gate->canUse($user)) {
            $msg = 'Bạn chưa được cấp quyền sử dụng X2AI. Vui lòng liên hệ quản trị viên.';
            $this->pushAssistant($msg);
            $this->logUsage(null, $text, $msg, 'context', 'rejected', 'low', false);
            $this->reset('attachments', 'pendingText', 'pendingScreenText');

            return;
        }

        $this->mode = $gate->effectiveMode($user);
        $risk = $gate->riskFor($this->mode);

        if ($gate->requiresApproval($risk)) {
            $msg = 'Yêu cầu này thuộc nhóm rủi ro cao và cần người có thẩm quyền phê duyệt trước khi X2AI thực hiện.';
            $this->pushAssistant($msg);
            $this->logUsage(null, $text, $msg, $this->mode, 'pending_approval', $risk, true);
            $this->reset('attachments', 'pendingText', 'pendingScreenText');

            return;
        }

        // History = all turns except the current user bubble (last entry); cap to the last
        // 16 turns to bound token cost. Current turn is rebuilt as content blocks (text +
        // attachments) so vision/PDF reach the API.
        $history = array_slice(array_slice($this->messages, 0, -1), -16);
        $apiMessages = array_map(fn ($m) => ['role' => $m['role'], 'content' => $m['content']], $history);

        $blocks = [];
        if ($text !== '') {
            $blocks[] = ['type' => 'text', 'text' => $text];
        }
        foreach ($this->attachments as $file) {
            $blocks = array_merge($blocks, $this->fileToBlocks($file));
        }
        $apiMessages[] = ['role' => 'user', 'content' => $blocks];

        // KB search luôn bật (kết quả tự lọc theo quyền xem của user); Mode 2 thêm lookup_data.
        $tools = [X2aiClient::knowledgeSearchTool()];
        if ($this->mode === 'data') {
            $tools[] = X2aiClient::dataLookupTool();
        }
        $client = app(X2aiClient::class);
        $reply = $client->ask($apiMessages, $this->systemPrompt(), $tools);

        $this->pushAssistant($reply);

        // Audit every real AI turn (WEB-UX-09 governance / CLAUDE.md AI audit rule).
        $this->logUsage($client, $text, $reply, $this->mode, $client->lastStatus, $risk, false);

        $this->reset('attachments', 'pendingText', 'pendingScreenText');
    }

    /** Append an assistant turn, pre-rendering its Markdown to styled HTML. */
    private function pushAssistant(string $text): void
    {
        $this->messages[] = ['role' => 'assistant', 'content' => $text, 'html' => $this->toHtml($text)];
        $this->saveMessage('assistant', $text);
        $this->dispatch('x2ai-scroll');
    }

    /** Render Markdown (GFM: tables, lists, code…) to sanitized HTML for the chat bubble. */
    private function toHtml(string $text): string
    {
        try {
            $converter = new GithubFlavoredMarkdownConverter([
                'html_input' => 'strip',        // never trust raw HTML from the model
                'allow_unsafe_links' => false,
            ]);

            return (string) $converter->convert($text);
        } catch (\Throwable) {
            return nl2br(e($text));
        }
    }

    /**
     * Persist one ai_usage_logs row per turn so Trung tâm AI (09-01) usage metrics
     * and Governance & Audit (09-02) reflect real usage, not just seed data.
     * Wrapped so a logging failure never breaks the chat reply.
     */
    private function logUsage(?X2aiClient $client, string $prompt, string $reply, string $mode, string $status, string $risk, bool $requiresApproval): void
    {
        try {
            $user = auth()->user();
            $ctx = app(CurrentContext::class);
            $model = $client?->lastModel ?? config('services.x2ai.model');

            AiUsageLog::create([
                'tenant_id' => $user?->tenant_id,
                'project_id' => $ctx->project()?->id ?? $user?->project_id,
                'building_id' => $user?->building_id,
                'user_id' => $user?->id,
                'actor_name' => $user?->name ?? 'Hệ thống',
                'surface' => $this->surfaceLabel(),
                'mode' => $mode === 'data' ? 'lookup' : 'context',
                'model' => $model,
                'action' => $mode === 'data' ? 'lookup' : 'chat',
                'risk_level' => $risk,
                'status' => $status,
                'requires_approval' => $requiresApproval,
                'prompt_excerpt' => Str::limit($prompt !== '' ? $prompt : '(đính kèm tệp)', 180),
                'response_excerpt' => Str::limit($reply, 480),
                'tokens_in' => $client?->lastInputTokens ?? 0,
                'tokens_out' => $client?->lastOutputTokens ?? 0,
                'latency_ms' => $client?->lastLatencyMs ?? 0,
                'cost' => $this->estimateCostVnd($model, $client?->lastInputTokens ?? 0, $client?->lastOutputTokens ?? 0),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** A short label for which screen invoked the copilot (matches seeder surface style). */
    private function surfaceLabel(): string
    {
        if (! empty($this->pageContext['title'])) {
            return Str::limit($this->pageContext['title'], 120, '');
        }
        if ($this->screenText && preg_match('~^URL:\s*(\S+)~', $this->screenText, $m)) {
            return ltrim($m[1], '/') ?: 'copilot';
        }

        return 'copilot';
    }

    /** Anthropic list price (USD/token) → VND, mirroring the seeder's cost convention. */
    private function estimateCostVnd(?string $model, int $tokensIn, int $tokensOut): float
    {
        [$inUsd, $outUsd] = match (true) {
            str_contains((string) $model, 'sonnet') => [3 / 1_000_000, 15 / 1_000_000],
            str_contains((string) $model, 'opus') => [15 / 1_000_000, 75 / 1_000_000],
            default => [1 / 1_000_000, 5 / 1_000_000], // haiku
        };

        return round(($tokensIn * $inUsd + $tokensOut * $outUsd) * 24000, 2);
    }

    /** Convert an uploaded image/PDF into Messages API content blocks. */
    private function fileToBlocks($file): array
    {
        $mime = $file->getMimeType();
        $data = base64_encode($file->get());

        if ($mime === 'application/pdf') {
            return [['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $data]]];
        }

        return [['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $data]]];
    }

    private function systemPrompt(): string
    {
        $ctx = app(CurrentContext::class);
        $user = auth()->user();

        $lines = [
            'Bạn là X2AI — trợ lý vận hành trong hệ thống quản lý toà nhà X2-BMS.',
            'Trả lời ngắn gọn, chính xác, bằng tiếng Việt theo nghiệp vụ quản lý vận hành chung cư/toà nhà.',
            'Không bịa số liệu; nếu cần dữ liệu thật hãy hướng dẫn người dùng tới màn hình tương ứng.',
            'Định dạng câu trả lời bằng Markdown: dùng bảng Markdown khi trình bày dữ liệu nhiều cột, '
                .'dùng tiêu đề/in đậm/danh sách để dễ đọc. Không trả về thẻ HTML thô.',
        ];

        // --- Chính sách AI đang áp dụng (đọc từ ai_policies, không hardcode) ---
        if ($guidelines = app(X2aiPolicyGate::class)->guidelines()) {
            $lines[] = 'Tuân thủ các chính sách AI đang áp dụng:';
            foreach ($guidelines as $g) {
                $lines[] = '- '.$g;
            }
        }

        // --- Người dùng & phạm vi quyền ---
        if ($user) {
            $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->implode(', ') : '';
            $lines[] = 'Người dùng: '.$user->name.($user->title ? ' ('.$user->title.')' : '').'.';
            $lines[] = 'Workspace: '.$ctx->workspaceLabel().($roles ? ' · vai trò: '.$roles : '').'.';
        }
        if ($project = $ctx->project()) {
            $lines[] = 'Dự án đang làm việc: '.$project->name.'.';
        }

        // --- Màn hình user đang có quyền (theo workspace) ---
        $lines[] = 'Các màn hình người dùng có thể truy cập: '.implode('; ', $this->accessibleScreens()).'.';

        // --- Màn hình hiện tại ---
        if (! empty($this->pageContext['title'])) {
            $lines[] = 'Màn hình hiện tại: '.$this->pageContext['title'].'.';
        }
        foreach ($this->pageContext['suggestions'] ?? [] as $s) {
            $lines[] = '- Gợi ý màn hình: '.($s['title'] ?? '').' '.($s['sub'] ?? '');
        }

        // --- Nội dung thật đang hiển thị (đọc từ DOM phía client) ---
        if ($this->screenText) {
            $lines[] = 'Nội dung đang hiển thị trên màn hình (trích từ giao diện người dùng, có thể gồm số liệu/bảng/nút thao tác):';
            $lines[] = '"""'."\n".$this->screenText."\n".'"""';
            $lines[] = 'Hãy trả lời bám sát nội dung màn hình trên khi câu hỏi liên quan tới những gì người dùng đang xem.';
        }

        $lines[] = 'Dùng công cụ search_knowledge để tra cứu tài liệu nội bộ (quy trình, hướng dẫn, chính sách, nội quy) '
            .'trong Cơ sở tri thức — kết quả đã tự giới hạn theo quyền xem của người dùng, hãy trả lời có trích dẫn tên tài liệu.';
        if ($this->mode === 'data') {
            $lines[] = 'Bạn được phép dùng công cụ lookup_data để tra cứu dữ liệu thật khi cần số liệu/bản ghi cụ thể.';
        }
        $lines[] = 'Người dùng có thể đính kèm ảnh/PDF; hãy đọc và phân tích nội dung tệp khi được cung cấp.';

        return implode("\n", $lines);
    }

    /** Screens the current workspace exposes — gives the model an access-aware map. */
    private function accessibleScreens(): array
    {
        $ctx = app(CurrentContext::class);
        $workspace = $ctx->workspace();

        $base = [
            'Tổng quan', 'Cư dân', 'Căn hộ', 'Duyệt cư dân', 'Phương tiện & thẻ',
            'Loại phí', 'Biểu giá', 'Kỳ phí', 'Bảng kê phí', 'Thanh toán', 'Đối soát ngân hàng',
            'Duyệt bảng kê', 'Phản ánh & yêu cầu', 'Công việc', 'An ninh',
        ];

        return match ($workspace) {
            'superadmin' => array_merge($base, ['Quản trị tenant', 'Gói SaaS', 'Tích hợp & API', 'Nhật ký hệ thống']),
            'hq' => array_merge($base, ['Dashboard đa dự án', 'Chính sách dùng chung', 'Nhân sự đa tòa']),
            default => $base, // bql
        };
    }

    public function render()
    {
        return view('livewire.x2ai-chat');
    }
}
