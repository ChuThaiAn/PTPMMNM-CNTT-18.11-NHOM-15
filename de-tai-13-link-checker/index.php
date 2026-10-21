<?php
$pdo = new PDO('mysql:host=localhost;dbname=link_checker_db;charset=utf8mb4', 'root', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startUrl = $_POST['start_url'];
    $maxPages = (int)$_POST['max_pages'];
    $maxDepth = (int)$_POST['max_depth'];

    $stmt = $pdo->prepare("INSERT INTO scan_runs (start_url, max_pages, max_depth, status) VALUES (?, ?, ?, 'running')");
    $stmt->execute([$startUrl, $maxPages, $maxDepth]);
    $scanRunId = $pdo->lastInsertId();

    require_once 'crawler.php';
    $crawler = new WebCrawler($pdo, $scanRunId, $startUrl, $maxPages, $maxDepth);
    $crawler->start($startUrl);

    $pdo->prepare("UPDATE scan_runs SET status = 'completed' WHERE id = ?")->execute([$scanRunId]);
    
    echo "<p style='color: green;'><b>Đã hoàn thành lượt quét #$scanRunId</b></p>";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Công cụ kiểm tra liên kết lỗi (Đề tài 13)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Đề tài 13: Công cụ kiểm tra liên kết và tài nguyên bị lỗi trên website</h2>
    <a href="compare.php">So sánh giữa 2 lượt quét</a>
    <hr>
    <form method="POST">
        <h3>Tạo Lượt Quét Mới</h3>
        <label>URL Bắt đầu:</label><br>
        <input type="text" name="start_url" style="width: 400px;" required value="http://localhost/"><br><br>
        <label>Số trang tối đa:</label><br>
        <input type="number" name="max_pages" value="20"><br><br>
        <label>Độ sâu tối đa (Depth):</label><br>
        <input type="number" name="max_depth" value="3"><br><br>
        <button type="submit">Bắt đầu Quét</button>
    </form>

    <h3>Danh Sách Các Liên Kết & Tài Nguyên Bị Lỗi</h3>
    <table>
        <tr>
            <th>Mã Lượt Quét</th>
            <th>Trang Nguồn</th>
            <th>URL Đích Lỗi</th>
            <th>Loại</th>
            <th>Mã Trạng Thái</th>
            <th>Loại Lỗi</th>
        </tr>
        <?php
        $sql = "SELECT l.*, p.url as source_url 
                FROM links l 
                JOIN pages p ON l.source_page_id = p.id 
                WHERE l.status_code != 200 
                ORDER BY l.id DESC LIMIT 50";
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>
                <td>#{$row['scan_run_id']}</td>
                <td>{$row['source_url']}</td>
                <td>{$row['target_url']}</td>
                <td>{$row['resource_type']}</td>
                <td>{$row['status_code']}</td>
                <td>{$row['error_type']}</td>
            </tr>";
        }
        ?>
    </table>
</body>
</html>