<?php

namespace App\Livewire;

use App\Support\Context\CurrentContext;
use App\Support\X2AI\X2aiClient;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * WEB-UX-09 — X2AI Copilot chat (embedded in the global floating FAB).
 *
 * Two modes:
 *   - 'context' : reads the current screen + the user's accessible screens &
 *                 permissions; user can attach images/PDFs for the model to read.
 *   - 'data'    : enables the lookup_data tool so the model can query X2-BMS data
 *                 via X2aiDataConnector (real API wired later).
 */
class X2aiChat extends Component
{
    use WithFileUploads;

    /** @var array<int, array{role:string, content:string}> */
    public array $messages = [];

    public string $input = '';

    /** context | data */
    public string $mode = 'context';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $attachments = [];

    public ?array $pageContext = null;

    /** Live text of the rendered screen, captured from the DOM on the client at send time. */
    public ?string $screenText = null;

    public function mount(?array $pageContext = null): void
    {
        $this->pageContext = $pageContext;
    }

    /** Prefill the input from a "Gợi ý nhanh" / Support Copilot button on any screen. */
    #[On('x2ai-prefill')]
    public function prefill(string $prompt = ''): void
    {
        $this->input = $prompt;
    }

    public function send(?string $screenText = null): void
    {
        // Captured from the client DOM (window.x2aiCaptureScreen) — what the user actually sees.
        $this->screenText = $screenText ? mb_substr($screenText, 0, 6000) : null;

        $text = trim($this->input);
        if ($text === '' && empty($this->attachments)) {
            return;
        }

        $this->validate([
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,webp,pdf',
        ]);

        // Build the API message history (prior turns as plain text).
        $apiMessages = array_map(
            fn ($m) => ['role' => $m['role'], 'content' => $m['content']],
            $this->messages,
        );

        // Current turn: text + any attachment blocks (vision / PDF).
        $blocks = [];
        if ($text !== '') {
            $blocks[] = ['type' => 'text', 'text' => $text];
        }
        foreach ($this->attachments as $file) {
            $blocks = array_merge($blocks, $this->fileToBlocks($file));
        }
        $apiMessages[] = ['role' => 'user', 'content' => $blocks];

        // Display the user turn (text + attachment note).
        $note = $this->attachments ? ' 📎 '.count($this->attachments).' tệp' : '';
        $this->messages[] = ['role' => 'user', 'content' => ($text !== '' ? $text : '(đính kèm)').$note];

        $tools = $this->mode === 'data' ? [X2aiClient::dataLookupTool()] : [];
        $reply = app(X2aiClient::class)->ask($apiMessages, $this->systemPrompt(), $tools);

        $this->messages[] = ['role' => 'assistant', 'content' => $reply];
        $this->reset('input', 'attachments');
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
            'Không bịa số liệu; nếu cần dữ liệu thật hãy hướng dẫn người dùng tới màn hình tương ứng (hoặc bật chế độ Tra cứu CSDL).',
        ];

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

        if ($this->mode === 'data') {
            $lines[] = 'Bạn được phép dùng công cụ lookup_data để tra cứu dữ liệu thật khi cần số liệu/bản ghi cụ thể.';
        } else {
            $lines[] = 'Người dùng có thể đính kèm ảnh/PDF; hãy đọc và phân tích nội dung tệp khi được cung cấp.';
        }

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
