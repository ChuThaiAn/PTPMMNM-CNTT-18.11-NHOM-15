<?php
$pdo = new PDO('mysql:host=localhost;dbname=link_checker_db;charset=utf8mb4', 'root', '');

$run1 = $_GET['run1'] ?? null;
$run2 = $_GET['run2'] ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>So sánh Lượt Quét (Diff Report)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>So Sánh Lỗi Giữa 2 Lượt Quét</h2>
    <a href="index.php"> Quay lại trang chủ</a>
    <hr>
    <form method="GET">
        <label>Mã Lượt 1 (Cũ):</label>
        <input type="number" name="run1" required value="<?= htmlspecialchars($run1) ?>">
        <label>Mã Lượt 2 (Mới):</label>
        <input type="number" name="run2" required value="<?= htmlspecialchars($run2) ?>">
        <button type="submit">So sánh</button>
    </form>

    <?php if ($run1 && $run2): ?>
        <h3>1. Các Lỗi Mới Xuất Hiện Trong Lượt #<?= htmlspecialchars($run2) ?></h3>
        <table>
            <tr><th>URL Đích Lỗi</th><th>Mã Lỗi</th></tr>
            <?php
            $sqlNew = "SELECT target_url, status_code FROM links 
                       WHERE scan_run_id = ? AND status_code != 200 
                       AND target_url NOT IN (SELECT target_url FROM links WHERE scan_run_id = ? AND status_code != 200)";
            $stmt = $pdo->prepare($sqlNew);
            $stmt->execute([$run2, $run1]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr><td>{$row['target_url']}</td><td>{$row['status_code']}</td></tr>";
            }
            ?>
        </table>

        <h3>2. Các Lỗi Đã Được Khắc Phục Ở Lượt #<?= htmlspecialchars($run2) ?></h3>
        <table>
            <tr><th>URL Đích Đã Sửa</th></tr>
            <?php
            $sqlFixed = "SELECT target_url FROM links 
                         WHERE scan_run_id = ? AND status_code != 200 
                         AND target_url NOT IN (SELECT target_url FROM links WHERE scan_run_id = ? AND status_code != 200)";
            $stmt = $pdo->prepare($sqlFixed);
            $stmt->execute([$run1, $run2]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr><td>{$row['target_url']}</td></tr>";
            }
            ?>
        </table>
    <?php endif; ?>
</body>
</html>