<?php
require_once 'config.php';

session_start();

// 生成 CSRF Token（防跨站请求伪造）
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['token'];

// 处理留言提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证 Token
    if (!hash_equals($_SESSION['token'], $_POST['token'] ?? '')) {
        $error = "无效请求（Token 错误）";
    } else {
        $name = trim($_POST['name']);
        $message = trim($_POST['message']);

        if (!empty($name) && !empty($message)) {
            $stmt = $pdo->prepare("INSERT INTO messages (name, message) VALUES (?, ?)");
            $stmt->execute([$name, $message]);
            $_SESSION['success'] = "留言提交成功！";
            // 重定向清空 POST 数据并刷新 Token
            header("Location: index.php");
            exit;
        } else {
            $error = "姓名和留言都不能为空！";
        }
    }
}

// 分页逻辑
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// 获取总记录数
$totalStmt = $pdo->query("SELECT COUNT(*) FROM messages");
$total = (int)$totalStmt->fetchColumn();
$totalPages = ceil($total / $limit);

// 获取当前页留言
$stmt = $pdo->prepare("SELECT id, name, message, created_at FROM messages ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll();

// 显示成功/错误消息（一次性）
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>留言板（带分页与删除）</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 30px auto; padding: 20px; line-height: 1.6; }
        h1 { text-align: center; color: #333; }
        form { border: 1px solid #ddd; padding: 15px; margin-bottom: 25px; background: #f9f9f9; }
        input, textarea { width: 100%; padding: 8px; margin: 6px 0; box-sizing: border-box; }
        button { background: #4CAF50; color: white; padding: 10px 15px; border: none; cursor: pointer; }
        .message { border-bottom: 1px dashed #ccc; padding: 12px 0; position: relative; }
        .name { font-weight: bold; color: #2c3e50; }
        .time { color: #7f8c8d; font-size: 0.9em; }
        .delete-link {
            position: absolute; right: 0; top: 12px;
            color: red; text-decoration: none; font-size: 0.9em;
        }
        .delete-link:hover { text-decoration: underline; }
        .alert { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .pagination { text-align: center; margin-top: 20px; }
        .pagination a, .pagination span {
            display: inline-block; padding: 5px 10px; margin: 0 3px;
            text-decoration: none; border: 1px solid #ddd;
        }
        .pagination .active { background: #4CAF50; color: white; }
    </style>
</head>
<body>

<h1>💬 留言板</h1>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
    <input type="text" name="name" placeholder="姓名（必填）" required>
    <textarea name="message" rows="4" placeholder="留言内容（必填）" required></textarea>
    <button type="submit">提交留言</button>
</form>

<h2>留言列表（共 <?php echo $total; ?> 条）</h2>

<?php if ($messages): ?>
    <?php foreach ($messages as $msg): ?>
        <div class="message">
            <div class="name"><?php echo htmlspecialchars($msg['name']); ?></div>
            <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
            <div class="time"><?php echo date('Y-m-d H:i:s', strtotime($msg['created_at'])); ?></div>
            <a href="delete.php?id=<?php echo $msg['id']; ?>&token=<?php echo urlencode($_SESSION['token']); ?>" 
               class="delete-link" 
               onclick="return confirm('确定要删除这条留言吗？')">🗑️ 删除</a>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>暂无留言。</p>
<?php endif; ?>

<!-- 分页 -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i == $page): ?>
            <span class="active"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

</body>
</html>