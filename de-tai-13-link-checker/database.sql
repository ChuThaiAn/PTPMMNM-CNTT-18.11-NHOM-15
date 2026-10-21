CREATE DATABASE IF NOT EXISTS link_checker_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE link_checker_db;

-- Bảng lưu cấu hình & lịch sử các lượt quét
CREATE TABLE IF NOT EXISTS scan_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_url VARCHAR(255) NOT NULL,
    max_pages INT DEFAULT 50,
    max_depth INT DEFAULT 3,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng lưu các trang web đã duyệt (Crawled Pages)
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scan_run_id INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    status_code INT,
    depth INT,
    FOREIGN KEY (scan_run_id) REFERENCES scan_runs(id) ON DELETE CASCADE
);

-- Bảng lưu danh sách các liên kết & ảnh phát hiện được
CREATE TABLE IF NOT EXISTS links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scan_run_id INT NOT NULL,
    source_page_id INT NOT NULL,
    target_url VARCHAR(500) NOT NULL,
    resource_type VARCHAR(50) NOT NULL, -- 'link' hoặc 'image'
    status_code INT,
    error_type VARCHAR(100), -- 'broken_http_404', 'timeout_or_connection_error', etc.
    FOREIGN KEY (scan_run_id) REFERENCES scan_runs(id) ON DELETE CASCADE,
    FOREIGN KEY (source_page_id) REFERENCES pages(id) ON DELETE CASCADE
);