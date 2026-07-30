<?php

namespace App\Services\Analytics\StoreReports;

use RuntimeException;

/**
 * Báo cáo tải về nhưng KHÔNG bóc được (đổi tên cột, đổi định dạng, file rỗng).
 *
 * Là exception riêng chứ không trả về mảng rỗng: mảng rỗng trông giống "hôm đó
 * không ai tải app", và số 0 giả trong báo cáo còn tệ hơn không có số. Store đổi
 * định dạng là chuyện có thật và cần người biết để sửa parser.
 */
class StoreReportFormatException extends RuntimeException {}
