# Cấu hình subdomain site tài liệu công khai (CloudPanel)

> Mục tiêu: phục vụ site tài liệu công khai tại **https://doc.x2.fino.vn** từ **chính app x2bms** (cùng codebase, cùng document root `public/`). App tự định tuyến theo host — không cần app/deploy riêng.

Phần code đã sẵn sàng (routing theo host + cột `is_public`); tài liệu này chỉ là các bước hạ tầng chủ dự án cần làm trên **CloudPanel**.

## 1. DNS
- Thêm bản ghi **A**: `doc.x2.fino.vn` → **IP server** (cùng IP đang chạy x2bms).
- Chờ DNS phân giải (kiểm tra: `nslookup doc.x2.fino.vn` hoặc `dig +short doc.x2.fino.vn`).

## 2. Thêm domain vào site x2bms (KHÔNG tạo site mới)
- CloudPanel → mở **site x2bms hiện tại** → tab **Domains** → **Add Domain** → nhập `doc.x2.fino.vn`.
- Dùng **chung document root `public/`** của app hiện tại (không tạo vhost/site mới). Mục đích: mọi request tới subdomain vào cùng `public/index.php` để Laravel tự route theo host (`config('docs.host')`).

## 3. SSL/TLS
- Tab **SSL/TLS** của site → cấp chứng chỉ **Let's Encrypt** cho `doc.x2.fino.vn` (thêm vào SAN cùng domain chính nếu CloudPanel cho phép, hoặc cấp riêng cho subdomain).
- Bật **Force HTTPS**.

## 4. ENV + cache
Trong `.env` của app (hoặc mục **Environment** của site trên CloudPanel):
```
DOCS_HOST=doc.x2.fino.vn
```
Rồi nạp lại cấu hình:
```bash
php artisan config:cache
```
> Nếu không set `DOCS_HOST`, mặc định trong `config/docs.php` đã là `doc.x2.fino.vn` — nhưng vẫn nên set tường minh để dễ đổi theo môi trường.

## 5. Kiểm tra
- Mở **https://doc.x2.fino.vn** → thấy **landing site tài liệu** (không phải redirect `/admin`).
- Landing **chỉ hiển thị space công khai** (hiện tại: "Vận hành & Tích hợp" — ops). Space nội bộ (dev/bql/hq/sa) **không** hiện với khách chưa đăng nhập.
- Mở một space nội bộ khi chưa đăng nhập → **chuyển hướng trang đăng nhập** (`/admin/login`).
- Host chính (vd `x2bms.test` / domain admin) mở `/` vẫn **redirect `/admin`** như cũ; `/docs` vẫn chạy.

## Phương án 2 — nếu CloudPanel không cho add nhiều domain chung 1 site
Nếu bản CloudPanel không hỗ trợ nhiều domain trên một site:
- Tạo **site mới** `doc.x2.fino.vn`, nhưng đặt **document root trỏ về CÙNG thư mục `public/`** của app x2bms (symlink hoặc cấu hình vhost trỏ chung path).
- Cấp Let's Encrypt cho site mới đó.
- Set `DOCS_HOST=doc.x2.fino.vn` (chung `.env` vì cùng codebase) + `php artisan config:cache`.
- **Không đổi code**: logic host-routing + `is_public` giữ nguyên; app nhận diện host qua `Request::getHost()`.

> Lưu ý: dù đi phương án nào, PHẢI cùng codebase/`public/` để chia sẻ session + cấu hình. Không deploy bản sao riêng.

## Quản trị nội dung công khai
- Bật/tắt công khai từng space: **panel `/sa` → nhóm "Tài liệu" → Không gian tài liệu → Toggle "Công khai"**.
- Mặc định seed: `ops` = công khai; `dev`/`bql`/`hq`/`sa` = nội bộ (yêu cầu đăng nhập). Chạy `php artisan docs:import --fresh` sẽ reset về mặc định này; import thường (không `--fresh`) không ghi đè lựa chọn công khai đã chỉnh tay.
