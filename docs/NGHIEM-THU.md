# Ma trận yêu cầu và kịch bản demo

| Yêu cầu đề | Thành phần | Bằng chứng phải thu khi chạy |
|---|---|---|
| 1. GitHub, README, tài khoản MSSV | README, .gitignore, toàn bộ mã/config | URL repository và ba mốc commit thực tế |
| 2. Web + DB + DB tool | app, mysql, phpmyadmin | Trang công khai, admin, bản ghi trong phpMyAdmin |
| 3. Nginx reverse proxy | nginx/default.conf | curl headers, request qua cổng 8088 |
| 4. Giám sát container/web/DB | Prometheus, Grafana, exporters, cAdvisor | Targets UP, nginx_up/mysql_up=1, dashboard có dữ liệu |
| 5. Log tập trung + 2–3 LogQL | Loki, Promtail, volume logs | 3 truy vấn README và log thực tế |
| 6. Hardening | Non-root, read-only, cap_drop, mạng internal, secrets | inspect container và port bind |
| 7. Báo cáo ≥10 trang | docs/BAO-CAO.md | Báo cáo hoàn thiện với thông tin SV và ảnh thực tế |

## Trình tự demo

1. `docker compose --profile linux-monitoring ps`: MySQL/app khỏe, init exited 0; các dịch vụ chạy. Mở `/health`, kiểm tra trả 200.
2. Mở trang công khai, đăng nhập admin bằng secret cục bộ. Thử mật khẩu sai để sinh `login_failed` (rate limit có thể trả 429 nếu thử liên tục).
3. Sửa tên/giới thiệu/kỹ năng; thêm dự án. Kiểm tra dữ liệu mới trên trang công khai và phpMyAdmin.
4. Bỏ chọn xuất bản, kiểm tra biến mất khỏi trang công khai; bật lại, sửa và xóa dự án thử. Đăng xuất, xác minh `/admin` chuyển về đăng nhập.
5. `curl -I http://localhost:8088` kiểm tra CSP, X-Frame-Options, X-Content-Type-Options. Truy cập trang không tồn tại để có log 404.
6. Mở Prometheus `/targets`: tất cả target của profile Ubuntu UP. Truy vấn `mysql_up`, `nginx_up`; mở Grafana dashboard, đợi ít nhất 30 giây.
7. Grafana Explore chạy ba LogQL trong README. Chụp cả truy vấn lẫn kết quả, chọn khoảng thời gian phù hợp.
8. `docker compose exec app id`, `docker compose exec nginx id`; `docker compose config` kiểm tra ports/networks/read_only/cap_drop. Không chụp nội dung secret.
9. Restart app, kiểm tra hồ sơ/dự án vẫn tồn tại. Tạo SQL backup; thử khôi phục trong môi trường riêng trước khi công bố khả năng phục hồi.

Ghi kết quả Pass/Fail, thời gian, môi trường và đường dẫn ảnh cho từng bước. Các bước này chưa được xác nhận chỉ vì đã có hướng dẫn.
