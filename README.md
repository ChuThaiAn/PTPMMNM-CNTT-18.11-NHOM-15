Đề tài 13: Công cụ kiểm tra liên kết và tài nguyên bị lỗi trên website
Giới thiệu
Ứng dụng Web phát triển bằng PHP thuần + MySQL giúp tự động duyệt trang web, thu thập và kiểm tra danh sách các liên kết (hyperlinks) và hình ảnh (images) bị hỏng (Mã HTTP 404, Timeout, Lỗi kết nối).

Cấu trúc dự án
index.php: Giao diện chính thực hiện lượt quét và xem danh sách lỗi.
crawler.php: Xử lý crawler, bóc tách URL, kiểm tra lỗi HTTP/Timeout.
compare.php: So sánh khác biệt giữa 2 lượt quét (lỗi mới phát sinh, lỗi đã khắc phục).
database.sql: Mã nguồn khởi tạo Cơ sở dữ liệu.
Hướng dẫn cài đặt
Import file database.sql vào MySQL/MariaDB.
Cấu hình kết nối PDO trong file index.php và compare.php.
Khởi chạy dự án thông qua Web Server (XAMPP / Apache / Nginx).
