<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * `/` không phải trang nội dung — `routes/web.php:13` chuyển hướng sang `/admin`
     * (trừ khi host là `config('docs.host')`, khi đó là reader tài liệu).
     *
     * Test scaffold mặc định của Laravel đòi 200 nên **đỏ vĩnh viễn từ đầu dự án**. Sửa
     * cho đúng hành vi thật thay vì để nó đỏ: một test luôn đỏ trong suite huấn luyện
     * người ta bỏ qua màu đỏ, và đó là cách lỗi thật bị lọt.
     */
    public function test_root_redirects_to_admin_panel(): void
    {
        $this->get('/')->assertRedirect('/admin');
    }
}
