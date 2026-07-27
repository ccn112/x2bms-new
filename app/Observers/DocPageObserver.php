<?php

namespace App\Observers;

use App\Models\DocPage;
use App\Models\DocPageRevision;
use Illuminate\Support\Facades\Auth;

/**
 * Quản lý version: sinh 1 revision mới mỗi khi trang được tạo hoặc khi
 * title/body thay đổi. Version tăng dần theo page.
 */
class DocPageObserver
{
    public function created(DocPage $page): void
    {
        $this->snapshot($page, 'Tạo trang');
    }

    public function updated(DocPage $page): void
    {
        // Chỉ chụp version khi nội dung thực sự đổi (title hoặc body).
        if ($page->wasChanged('title') || $page->wasChanged('body')) {
            $this->snapshot($page, 'Cập nhật nội dung');
        }
    }

    protected function snapshot(DocPage $page, string $note): void
    {
        $nextVersion = (int) DocPageRevision::where('page_id', $page->id)->max('version') + 1;

        DocPageRevision::create([
            'page_id' => $page->id,
            'version' => $nextVersion,
            'title' => $page->title,
            'body' => $page->body,
            'note' => $note,
            'editor_id' => $page->updated_by ?? Auth::id(),
            'created_at' => now(),
        ]);
    }
}
