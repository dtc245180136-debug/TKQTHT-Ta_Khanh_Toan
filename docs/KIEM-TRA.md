# Kết quả kiểm tra — 24/10/2026

Đã thực hiện trong workspace Windows:

- Script `deploy/setup.ps1` chạy thành công, tạo secrets cục bộ ngoài Git và cấu hình MySQL exporter; không in mật khẩu.
- `docker compose config --quiet`: thành công, Compose đọc được cấu hình cùng các secret đã tạo.
- Python PyYAML đọc thành công tất cả 6 file YAML.
- Dashboard Grafana parse JSON thành công, có 8 panel cho targets, Nginx, MySQL, CPU/RAM và audit logs.
- `php-parser` kiểm tra cú pháp PHP 8.3 cho bootstrap/schema/index/project-form: thành công. Đây là parser tĩnh, chưa phải PHP runtime.
- PowerShell parser kiểm tra setup.ps1: thành công.

## Chạy thử Docker Desktop thực tế

Đã khởi động Docker Desktop Linux Engine 29.8.2, tải image và build thành công PHP/Nginx. Stack đang chạy với profile `linux-monitoring`; init thoát mã 0, MySQL và app healthy.

Kiểm tra tích hợp bằng `python deploy/smoke-test.py` đã đạt:

- `/health` kết nối MySQL, trả trạng thái ok.
- Đăng nhập admin, tạo dự án và xem công khai.
- Sửa dự án và ẩn khỏi trang công khai, xóa dự án thử.
- Đăng xuất, kiểm tra trang admin yêu cầu đăng nhập.
- Nginx có CSP, X-Frame-Options và X-Content-Type-Options.
- phpMyAdmin, Grafana và Prometheus trả HTTP 200.

Kiểm tra container: `nginx -t`, PHP `php -l`, `promtool check config` đều thành công. Cấu hình Prometheus đã bổ sung mạng tools để cổng localhost truy cập được trên Docker Desktop. cAdvisor đã đổi từ 0.49.1 sang `ghcr.io/google/cadvisor:0.55.1` sau khi bản cũ lỗi đọc storage Docker 29; bản mới khởi động và thu thập được số liệu.

Prometheus: cả 4 target `prometheus`, `mysql`, `nginx`, `containers` có `up=1`; `mysql_up=1`, `nginx_up=1`. Truy vấn bộ nhớ container với nhãn image khác rỗng trả 19 series trong thời điểm kiểm tra (bao gồm container khác trên Docker host).

Grafana API xác nhận dashboard Portfolio Operations có 8 panel. Loki qua Grafana datasource proxy trả success và có dữ liệu cho log ứng dụng, access log Nginx và truy vấn đếm `project_saved` trong 5 phút. Chưa kiểm thử riêng truy vấn login_failed hoặc lỗi HTTP trong lần chạy này.

Đã mở trang công khai trong trình duyệt và kiểm tra ảnh giao diện desktop. Hai dự án mẫu vẫn được giữ; dự án tạm của smoke test đã xóa. Chưa kiểm tra responsive trên thiết bị thật, chưa chạy script cài đặt Ubuntu hoặc thử backup/restore.

Chạy quy trình `docs/NGHIEM-THU.md` trên Ubuntu mục tiêu để nghiệm thu môi trường nộp bài. Chưa có Git repository tại workspace nên chưa commit/push GitHub. Khung báo cáo chưa có thông tin sinh viên hoặc ảnh nghiệm thu và chưa phải tài liệu 10 trang hoàn thiện.
