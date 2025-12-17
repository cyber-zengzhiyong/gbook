<?php
require_once 'config.php';
session_start();

// 验证 Token（防 CSRF）
if (!isset($_GET['token']) || !hash_equals($_SESSION['token'], $_GET['token'])) {
    $_SESSION['error'] = "无效操作（Token 错误）";
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "无效的留言 ID";
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// 简单密码验证（通过 POST 或 JS 弹窗，这里用 GET + 确认页简化）
// 实际可改为 POST 表单输入密码，但为简化，我们直接在 URL 后加密码（不推荐生产！）
// 更安全做法：跳转到密码输入页 → 这里我们用 JS 础认 + 密码提示

// 临时方案：使用 JS 弹窗输入密码
// 但 PHP 无法直接获取 JS 输入，所以改为：删除链接跳转到带密码的确认页（略复杂）
// 折中：我们要求用户在删除前知道密码，并在链接中传递（仅演示，不安全！）

// ⚠️ 以下仅为教学演示！实际应使用 POST 表单输入密码并验证。
// 更安全方式请用登录态或独立密码验证页。

// 这里我们改成：删除时重定向到一个确认页（简单处理）
// 但为快速实现，我们假设用户知道密码，并在 GET 中传递（仅用于演示）

// 如果你不想在 URL 传密码，可取消下面这段，改用 session 或表单

// 演示用：从 GET 获取密码（不安全！）
if (!isset($_GET['pwd']) || $_GET['pwd'] !== $delete_password) {
    // 跳转到密码输入页（简易）
    $id = (int)$_GET['id'];
    $token = urlencode($_GET['token']);
    echo <<<HTML
    <form method="GET" style="text-align:center; margin-top:100px;">
        <input type="hidden" name="id" value="$id">
        <input type="hidden" name="token" value="$token">
        <p>请输入删除密码：</p>
        <input type="password" name="pwd" required>
        <button type="submit">确认删除</button>
        <p><a href="index.php">取消</a></p>
    </form>
    HTML;
    exit;
}

// 密码正确，执行删除
$stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
$deleted = $stmt->execute([$id]);

if ($deleted) {
    $_SESSION['success'] = "留言已成功删除！";
} else {
    $_SESSION['error'] = "删除失败！";
}

// 刷新 Token
$_SESSION['token'] = bin2hex(random_bytes(32));

header("Location: index.php");
exit;
?>