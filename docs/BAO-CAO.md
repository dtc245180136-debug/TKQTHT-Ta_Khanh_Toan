# Khung báo cáo thực hành — Đề 13

Đây là khung để hoàn thiện báo cáo tối thiểu 10 trang, chưa phải báo cáo nghiệm thu có kết quả thực tế. Mỗi mục bên dưới dự kiến một trang khi trình bày Word/PDF; bổ sung ảnh thực tế, chú thích và kết quả để đạt yêu cầu số trang.

1. **Bìa**: trường, khoa, môn Triển khai và Quản trị Hệ thống Phần mềm, đề 13, họ tên, MSSV, lớp, giảng viên, ngày thực hiện.
2. **Mục tiêu và yêu cầu**: website portfolio và admin; MySQL/phpMyAdmin; các yêu cầu chung trong ma trận nghiệm thu. Phạm vi demo Docker Compose trên Ubuntu.
3. **Kiến trúc hệ thống**: vẽ browser → Nginx → PHP → MySQL; bổ sung monitoring/logging. Giải thích ba nhóm mạng edge/data/monitoring và công cụ bind localhost.
4. **Ứng dụng và dữ liệu**: mô tả bảng admins/profile/projects, mật khẩu băm, quan hệ trang công khai–admin. Chèn ảnh giao diện và dữ liệu phpMyAdmin, che thông tin nhạy cảm.
5. **Triển khai Docker**: thông số Ubuntu/Docker/Compose đã dùng, quy trình install-ubuntu.sh, secret generation, init và healthcheck; chèn kết quả compose ps.
6. **Nginx reverse proxy**: giải thích cổng 8088, upstream app, security headers, rate limit; chèn curl headers và cấu hình liên quan. Nêu rõ demo HTTP với security headers, TLS là phần mở rộng.
7. **Prometheus và Grafana**: scrape nginx-exporter/mysql-exporter/cAdvisor, chu kỳ 15s, datasource provision; ảnh targets và dashboard, phân biệt up với mysql_up/nginx_up.
8. **Loki, Promtail và LogQL**: đường đi log từ named volume đến Loki; phân tích ba truy vấn README; chèn ảnh kết quả lỗi HTTP, login_failed, số lần project_saved. Nêu tình trạng EOL của Promtail.
9. **Hardening, vận hành và phục hồi**: non-root/read-only, CSRF/SQLi/XSS, mạng internal, secret ngoài Git; ngoại lệ init/phpMyAdmin/cAdvisor; backup, giới hạn log và kiểm tra khôi phục.
10. **Kết quả, đánh giá và tài liệu tham khảo**: điền từng kết quả nghiệm thu thực tế, URL GitHub theo MSSV, ba commit SHA, hạn chế và hướng phát triển PHP-FPM/TLS/Alloy. Trích nguồn tài liệu chính thức liên kết trong README.

Không ghi “đã triển khai thành công” nếu chưa có kết quả kiểm thử tương ứng. Chèn hình có số thứ tự, tên hình và mô tả kết quả quan sát được. Bổ sung mục lục, danh mục hình nếu định dạng báo cáo yêu cầu.
