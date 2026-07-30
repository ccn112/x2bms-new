{{--
    Ảnh chứng từ chuyển khoản do cư dân nộp. Mở ở kích thước lớn nhất có sẵn:
    BQL phải đọc được số tiền và mã tham chiếu trên ảnh mới đối chiếu được sao kê,
    nên KHÔNG dùng biến thể thumb ở đây.
--}}
<div class="space-y-4">
    @forelse ($attachments as $a)
        <figure class="space-y-2">
            {{-- `public_url`, KHÔNG phải cột thô `url`: cột đó thường rỗng và
                 accessor mới là chỗ dựng đường dẫn từ disk+path. --}}
            <a href="{{ $a->public_url }}" target="_blank" rel="noopener">
                <img src="{{ $a->public_url }}" alt="Chứng từ {{ $a->file_name }}"
                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700" />
            </a>
            <figcaption class="text-xs text-gray-500 dark:text-gray-400">
                {{ $a->file_name }} · bấm vào ảnh để mở tab mới xem cỡ thật
            </figcaption>
        </figure>
    @empty
        <p class="text-sm text-danger-600">
            Khai báo này không có ảnh chứng từ — không đối chiếu được với sao kê.
        </p>
    @endforelse
</div>
