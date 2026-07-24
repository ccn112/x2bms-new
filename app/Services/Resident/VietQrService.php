<?php

namespace App\Services\Resident;

/**
 * Sinh mã VietQR (napas 24/7) chuẩn EMVCo cho chuyển khoản tới tài khoản thụ hưởng
 * của tenant/dự án, với SỐ TIỀN + NỘI DUNG lấy từ hoá đơn.
 *
 * Trả về: chuỗi EMVCo (app render QR offline bằng qr_flutter) + URL ảnh img.vietqr.io
 * (tiện lợi) + danh sách app ngân hàng để deeplink. Không cần credential (chỉ cần số
 * tài khoản người nhận). Xem docs/api/RESIDENT_API_REFERENCE.md.
 */
class VietQrService
{
    /**
     * @param  array{bank_bin:string,bank_code?:string,account_no:string,account_name?:string}  $bank
     * @return array<string,mixed>
     */
    public function build(array $bank, string $amount, string $content): array
    {
        $bin = $bank['bank_bin'];
        $accountNo = $bank['account_no'];
        // Số tiền VietQR = số nguyên đồng (không thập phân).
        $amountInt = (string) (int) round((float) $amount);
        // Nội dung: chỉ chữ/số/khoảng trắng cơ bản (napas giới hạn ký tự).
        $addInfo = $this->sanitize($content);

        $qrString = $this->buildEmv($bin, $accountNo, $amountInt, $addInfo);

        $imageBank = $bank['bank_code'] ?? $bin;
        $imageUrl = 'https://img.vietqr.io/image/'.$imageBank.'-'.$accountNo.'-compact2.png'
            .'?amount='.$amountInt
            .'&addInfo='.rawurlencode($addInfo)
            .(isset($bank['account_name']) ? '&accountName='.rawurlencode($bank['account_name']) : '');

        return [
            'qr_string' => $qrString,
            'qr_image_url' => $imageUrl,
            'bank' => [
                'bin' => $bin,
                'code' => $bank['bank_code'] ?? null,
                'account_no' => $accountNo,
                'account_name' => $bank['account_name'] ?? null,
            ],
            'amount' => $amountInt,
            'content' => $addInfo,
            'bank_apps' => $this->bankApps(),
        ];
    }

    /** Danh sách app ngân hàng để mở deeplink (từ config/vietnam_banks.php). */
    public function bankApps(): array
    {
        return array_map(fn ($b) => [
            'code' => $b['code'],
            'bin' => $b['bin'],
            'name' => $b['name'],
            'short_name' => $b['short_name'],
            'logo' => 'https://api.vietqr.io/img/'.$b['code'].'.png',
            'android_package' => $b['android'] ?? null,
            'ios_scheme' => $b['ios'] ?? null,
        ], config('vietnam_banks', []));
    }

    /** Dựng chuỗi EMVCo (dynamic QR, có số tiền) + CRC16. */
    private function buildEmv(string $bin, string $accountNo, string $amount, string $addInfo): string
    {
        // 38 — Merchant Account Information (napas).
        $acquirer = $this->tlv('00', $bin).$this->tlv('01', $accountNo);
        $merchantAccount =
            $this->tlv('00', 'A000000727')      // GUID napas
            .$this->tlv('01', $acquirer)        // beneficiary org (BIN + account)
            .$this->tlv('02', 'QRIBFTTA');      // service: chuyển tới tài khoản

        $payload =
            $this->tlv('00', '01')              // Payload Format Indicator
            .$this->tlv('01', '12')             // Point of Initiation: 12 = dynamic
            .$this->tlv('38', $merchantAccount)
            .$this->tlv('52', '0000')           // MCC
            .$this->tlv('53', '704')            // currency VND
            .$this->tlv('54', $amount)          // amount
            .$this->tlv('58', 'VN')             // country
            .$this->tlv('62', $this->tlv('08', $addInfo)); // additional data: purpose

        $payload .= '6304'; // CRC id + length, giá trị tính trên toàn chuỗi kể cả "6304"
        $crc = $this->crc16($payload);

        return $payload.$crc;
    }

    /** TLV: id(2) + length(2, zero-pad) + value. */
    private function tlv(string $id, string $value): string
    {
        return $id.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
    }

    /** CRC16-CCITT (poly 0x1021, init 0xFFFF), hex 4 ký tự in hoa. */
    private function crc16(string $data): string
    {
        $crc = 0xFFFF;
        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /** Bỏ dấu + ký tự đặc biệt cho nội dung QR (napas an toàn). */
    private function sanitize(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text));
        // Loại dấu tiếng Việt → ASCII gần đúng (đơn giản, tránh lệ thuộc intl).
        $from = ['à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ','è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ','ì','í','ị','ỉ','ĩ','ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ','ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ','ỳ','ý','ỵ','ỷ','ỹ','đ','À','Á','Â','Ă','È','É','Ê','Ì','Í','Ò','Ó','Ô','Ơ','Ù','Ú','Ư','Ý','Đ'];
        $to = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y','d','A','A','A','A','E','E','E','I','I','O','O','O','O','U','U','U','Y','D'];
        $text = str_replace($from, $to, $text);
        $text = preg_replace('/[^A-Za-z0-9 ]/', '', $text);

        return substr(trim($text), 0, 25);
    }
}
