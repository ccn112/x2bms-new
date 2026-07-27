<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Host site tài liệu công khai
    |--------------------------------------------------------------------------
    |
    | Subdomain phục vụ site tài liệu PUBLIC (doc.x2.fino.vn), trỏ về cùng app
    | x2bms. Khi request đến đúng host này, route root '/' map thẳng vào reader
    | tài liệu (landing) — không cần prefix '/docs'. Host chính vẫn giữ '/docs'.
    |
    */

    'host' => env('DOCS_HOST', 'doc.x2.fino.vn'),

];
