<?php
// 数据库配置
$host = 'localhost';
$dbname = 'guestbook';
$username = 'root';
$password = 'root';

// 删除密码（请修改为强密码）
$delete_password = 'admin123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}
?>