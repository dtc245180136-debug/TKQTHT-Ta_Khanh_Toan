# Đề 13 — Website Portfolio / Giới thiệu cá nhân

Dự án bổ sung bên cạnh `../hotel-booking`, giữ mô hình Docker Compose, Nginx, giám sát và log tập trung. PHP 8.3, MySQL 8.4 và phpMyAdmin đáp ứng stack của đề. Tất cả thành phần chạy bằng Docker Compose; không cần cài PHP trực tiếp trên Ubuntu.

## Chức năng

- Trang công khai: hồ sơ, kỹ năng, dự án đã xuất bản, liên hệ email.
- Quản trị: đăng nhập/đăng xuất, sửa hồ sơ, thêm/sửa/xóa dự án, ẩn/hiện dự án.
- PDO prepared statements, escape HTML, kiểm tra URL, mật khẩu băm, CSRF, cookie HttpOnly/SameSite, giới hạn đăng nhập tại Nginx.
- Tự tạo dữ liệu mẫu và tài khoản admin ở lần khởi tạo. Chạy lại không ghi đè hồ sơ hoặc mật khẩu đã có.

## Cài trên Ubuntu

Cài Git, Docker Engine, Docker Compose plugin và OpenSSL trước. Dùng tài khoản được phép chạy Docker. Cổng website mặc định 8088, tránh cổng 8000 của dự án khách sạn.

```bash
cd TKHT/portfolio
bash deploy/install-ubuntu.sh
docker compose ps
curl -f http://localhost:8088/health
```

Mở `http://IP-UBUNTU:8088`. Đăng nhập tại `/login`, tên `admin`, mật khẩu đọc bằng `cat secrets/admin_password` trên máy chủ. Sửa hồ sơ mẫu ngay trong quản trị. Script không tự cài gói hệ điều hành, không xóa volume, không đẩy mã lên GitHub.

| Thành phần | Địa chỉ trên máy chủ | Đăng nhập |
|---|---|---|
| Website | http://localhost:8088 | Admin tại /login |
| phpMyAdmin | http://localhost:8089 | portfolio / secrets/db_password |
| Grafana | http://localhost:3008 | admin / secrets/grafana_password |
| Prometheus | http://localhost:9098 | Chỉ localhost |

Các công cụ quản trị chỉ bind localhost. Từ máy cá nhân, mở SSH tunnel: `ssh -L 3008:localhost:3008 -L 8089:localhost:8089 -L 9098:localhost:9098 USER@IP-UBUNTU`. Sau đó truy cập các địa chỉ localhost trên máy cá nhân.

## Chạy trên Windows

Khởi động Docker Desktop ở chế độ Linux containers rồi chạy PowerShell:

```powershell
cd TKHT/portfolio
powershell -ExecutionPolicy Bypass -File deploy/setup.ps1
docker compose config --quiet
docker compose up -d --build
docker compose ps
```

cAdvisor dùng profile `linux-monitoring`, mặc định không bật trong lệnh Windows ở trên. Để bật: `docker compose --profile linux-monitoring up -d`. Đã kiểm tra cAdvisor 0.55.1 hoạt động trên Docker Desktop Linux Engine 29.8.2 của workspace này; vẫn cần kiểm tra trên Ubuntu mục tiêu trước buổi demo vì host mounts phụ thuộc môi trường.

## Kiến trúc và hardening

Browser → Nginx → PHP → MySQL. phpMyAdmin nối MySQL trên mạng data. Prometheus lấy số liệu Nginx exporter, MySQL exporter và cAdvisor. Grafana đọc Prometheus/Loki. Promtail đọc volume log ứng dụng và access log Nginx, không mount Docker socket.

Mạng `data` và `monitoring` là internal. MySQL, exporters, Loki và cAdvisor không mở cổng host. PHP và Nginx chạy non-root, filesystem read-only, tmpfs, drop ALL capabilities. Dịch vụ init chạy root trong thời gian ngắn để cấp quyền volume log; MySQL/phpMyAdmin/cAdvisor không áp dụng cùng mức non-root/read-only vì yêu cầu vận hành của image. cAdvisor có quyền đọc filesystem host, chỉ bật khi cần nghiệm thu. Mật khẩu sinh ngẫu nhiên, lưu ngoài Git. Log Docker giới hạn 3 × 10 MB/container; log file trong volume cần theo dõi dung lượng và lưu trữ/luân chuyển trong vận hành dài hạn.

Nginx có security headers cơ bản theo lựa chọn cho phép trong đề. Cấu hình mặc định là HTTP cho môi trường thực hành; không truyền mật khẩu qua Internet công cộng. Khi đưa lên máy chủ công khai, cấu hình TLS và bật cookie Secure tương ứng. PHP built-in server phù hợp demo học phần, cần thay bằng PHP-FPM cho tải thực tế.

## Giám sát và LogQL

Grafana được provision sẵn hai datasource và dashboard **Portfolio Operations**. Kiểm tra `up`, `nginx_up`, `mysql_up`, lưu lượng request và CPU/RAM container. `up=1` ở exporter chưa đảm bảo dịch vụ đích khỏe; cần `nginx_up=1` và `mysql_up=1`.

Vào Grafana → Explore → Loki, sau khi mở website và đăng nhập/sửa nội dung:

```logql
{job="portfolio-nginx"} | json | status >= 400
{job="portfolio-app"} | json | event = "login_failed"
sum(count_over_time({job="portfolio-app"} | json | event = "project_saved" [5m]))
```

Promtail được giữ để đáp ứng đề bài. Theo [Grafana](https://grafana.com/docs/loki/latest/send-data/promtail/), Promtail EOL ngày 02/03/2026; triển khai mới ngoài bài thực hành nên chuyển sang Alloy. MySQL exporter dùng file `.cnf` theo [tài liệu chính thức](https://github.com/prometheus/mysqld_exporter), tài khoản riêng chỉ phục vụ giám sát.

## Vận hành

```bash
docker compose logs --tail=100 app nginx mysql
docker compose --profile linux-monitoring up -d --build
bash deploy/backup.sh
docker compose stop
docker compose start
```

Khôi phục từ SQL: `docker compose exec -T mysql sh -c 'MYSQL_PWD="$(cat /run/secrets/db_password)" mysql -u portfolio portfolio' < backups/TEN-FILE.sql`. Kiểm tra bản sao lưu trước khi khôi phục vì dữ liệu hiện có có thể bị ghi đè. Không dùng `down -v` khi muốn giữ dữ liệu. Sao lưu riêng thư mục secrets và lưu bản backup ngoài máy chủ. Thay file secret không tự thay mật khẩu trong MySQL đã khởi tạo; cần đổi tài khoản DB tương ứng và restart dịch vụ.

## GitHub và báo cáo

Xem [ma trận nghiệm thu](docs/NGHIEM-THU.md) và [khung báo cáo 10 trang](docs/BAO-CAO.md). Người học điền họ tên/MSSV, tạo tài khoản và repository theo mã số sinh viên. Không có repository Git tại thư mục hiện tại nên chưa tạo commit hoặc push.

Gợi ý ba mốc commit sau khi kiểm chứng từng phần:

1. `feat: portfolio admin mysql phpmyadmin and nginx hardening` — ứng dụng, DB, reverse proxy.
2. `feat: prometheus grafana exporters and container monitoring` — giám sát.
3. `feat: loki promtail logql and deployment documentation` — log tập trung, tài liệu.

Không commit `.env`, `secrets/`, `backups/`, dữ liệu volume hoặc mật khẩu chụp trong màn hình. Các mốc trên là hướng dẫn, không phải lịch sử commit đã thực hiện.

## Trạng thái kiểm chứng

Xem `docs/KIEM-TRA.md` để biết chính xác kiểm tra đã chạy và phần còn cần xác minh bằng Docker Engine. Chưa coi tài liệu hoặc cấu hình có sẵn là bằng chứng triển khai thành công.
"# TKQTHT-Ta_Khanh_Toan" 
