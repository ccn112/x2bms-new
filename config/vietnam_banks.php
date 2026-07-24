<?php

/**
 * Danh mục ngân hàng VN (napas) cho VietQR + deeplink mở app ngân hàng.
 *
 * - `bin`: mã BIN napas (acquirer id) — dùng cho EMVCo QR + img.vietqr.io.
 * - `code`: mã ngắn (VietQR/napas).
 * - `android`: package name để mở app trên Android (best-effort).
 * - `ios`: URL scheme mở app trên iOS (best-effort; null nếu chưa rõ → app fallback QR scan).
 *
 * Deeplink mở app + tự quét là best-effort (VN chưa có chuẩn thống nhất). Luồng
 * chính: hiển thị QR để cư dân quét bằng bất kỳ app ngân hàng nào. Danh sách này
 * để app show nút "Mở app ngân hàng" tiện lợi. `logo` từ CDN vietqr.io.
 */
return [
    ['code' => 'VCB', 'bin' => '970436', 'name' => 'Vietcombank', 'short_name' => 'Vietcombank', 'android' => 'com.VCB', 'ios' => 'vietcombank://'],
    ['code' => 'TCB', 'bin' => '970407', 'name' => 'Techcombank', 'short_name' => 'Techcombank', 'android' => 'vn.com.techcombank.bb.app', 'ios' => 'tcbmobile://'],
    ['code' => 'MB', 'bin' => '970422', 'name' => 'MB Bank', 'short_name' => 'MBBank', 'android' => 'com.mbmobile', 'ios' => 'mbmobile://'],
    ['code' => 'ICB', 'bin' => '970415', 'name' => 'VietinBank', 'short_name' => 'VietinBank', 'android' => 'com.vietinbank.ipay', 'ios' => 'vietinbank://'],
    ['code' => 'BIDV', 'bin' => '970418', 'name' => 'BIDV', 'short_name' => 'BIDV', 'android' => 'com.vnpay.bidv', 'ios' => 'bidv://'],
    ['code' => 'VBA', 'bin' => '970405', 'name' => 'Agribank', 'short_name' => 'Agribank', 'android' => 'com.vnpay.Agribank3g', 'ios' => 'agribankmobile://'],
    ['code' => 'ACB', 'bin' => '970416', 'name' => 'ACB', 'short_name' => 'ACB', 'android' => 'mobile.acb.com.vn', 'ios' => 'acbapp://'],
    ['code' => 'VPB', 'bin' => '970432', 'name' => 'VPBank', 'short_name' => 'VPBank', 'android' => 'com.vnpay.vpbankonline', 'ios' => 'vpbankneo://'],
    ['code' => 'TPB', 'bin' => '970423', 'name' => 'TPBank', 'short_name' => 'TPBank', 'android' => 'com.tpb.mb.gprsandroid', 'ios' => 'tpb://'],
    ['code' => 'STB', 'bin' => '970403', 'name' => 'Sacombank', 'short_name' => 'Sacombank', 'android' => 'src.com.sacombank', 'ios' => 'sacombankpay://'],
    ['code' => 'HDB', 'bin' => '970437', 'name' => 'HDBank', 'short_name' => 'HDBank', 'android' => 'com.vnpay.hdbank', 'ios' => 'hdbank://'],
    ['code' => 'VIB', 'bin' => '970441', 'name' => 'VIB', 'short_name' => 'VIB', 'android' => 'com.vib.myvib2', 'ios' => 'myvib://'],
    ['code' => 'SHB', 'bin' => '970443', 'name' => 'SHB', 'short_name' => 'SHB', 'android' => 'vn.shb.mobile', 'ios' => 'shbmobile://'],
    ['code' => 'MSB', 'bin' => '970426', 'name' => 'MSB', 'short_name' => 'MSB', 'android' => 'vn.com.msb.smartBanking', 'ios' => 'msbmobile://'],
    ['code' => 'OCB', 'bin' => '970448', 'name' => 'OCB', 'short_name' => 'OCB', 'android' => 'vn.com.ocb.awardridic', 'ios' => 'ocbomni://'],
    ['code' => 'EIB', 'bin' => '970431', 'name' => 'Eximbank', 'short_name' => 'Eximbank', 'android' => 'com.vnpay.EximBankOmni', 'ios' => 'eximbank://'],
    ['code' => 'SEAB', 'bin' => '970440', 'name' => 'SeABank', 'short_name' => 'SeABank', 'android' => 'vn.com.seabank.mb', 'ios' => 'seamobile://'],
    ['code' => 'SCB', 'bin' => '970429', 'name' => 'SCB', 'short_name' => 'SCB', 'android' => 'vn.com.scb.scbmobilebanking', 'ios' => 'scbmobile://'],
];
