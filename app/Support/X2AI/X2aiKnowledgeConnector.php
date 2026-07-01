<?php

namespace App\Support\X2AI;

use App\Models\KnowledgeArticle;
use Illuminate\Support\Str;

/**
 * Backend cho tool `search_knowledge` — tìm trong Cơ sở tri thức (KB) NHỮNG tài
 * liệu người dùng hiện tại được phép xem (scopeVisibleTo → tôn trọng phân quyền
 * 3 cấp), trả về text đã trích (body + PDF/DOCX) để X2AI trả lời có căn cứ.
 */
class X2aiKnowledgeConnector
{
    private const MAX_RESULTS = 5;

    private const PER_DOC_CHARS = 3000;

    public function search(array $args): string
    {
        $user = auth()->user();
        if (! $user) {
            return 'Không xác định được người dùng để tra cứu KB.';
        }

        $q = trim((string) ($args['query'] ?? ''));
        if ($q === '') {
            return 'Cần từ khóa để tìm trong cơ sở tri thức.';
        }

        $articles = KnowledgeArticle::visibleTo($user)
            ->where('status', 'published')
            ->where(function ($w) use ($q) {
                $like = '%'.$q.'%';
                $w->where('title', 'like', $like)
                    ->orWhere('excerpt', 'like', $like)
                    ->orWhere('content_text', 'like', $like);
            })
            ->orderByDesc('views')
            ->limit(self::MAX_RESULTS)
            ->get();

        if ($articles->isEmpty()) {
            return "Không tìm thấy tài liệu KB phù hợp với \"{$q}\" trong phạm vi quyền của bạn.";
        }

        $out = ["Tìm thấy {$articles->count()} tài liệu KB liên quan (trong phạm vi quyền của bạn):"];
        foreach ($articles as $a) {
            $owner = KnowledgeArticle::OWNER_LEVEL[$a->owner_level] ?? $a->owner_level;
            $cat = $a->category?->name ?? 'Chung';
            $text = $a->content_text ?: app(\App\Support\Knowledge\DocumentTextExtractor::class)->htmlToText($a->body ?? '');
            $files = collect($a->attachments ?? [])->map(fn ($p) => basename($p))->implode(', ');

            $out[] = "\n### {$a->title}  (cấp: {$owner} · danh mục: {$cat})"
                .($a->excerpt ? "\nTóm tắt: {$a->excerpt}" : '')
                .($files ? "\nTệp đính kèm: {$files}" : '')
                ."\nNội dung:\n".Str::limit($text, self::PER_DOC_CHARS, '…');
        }

        return implode("\n", $out);
    }
}
