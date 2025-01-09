<?php
session_start();
include 'db.php';

// 检查管理员是否已登录
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    die(json_encode(['success' => false, 'message' => '未登录。']));
}

// 获取卡片 ID
$id = $_POST['id'];

// 删除卡片
$sql = "DELETE FROM cards WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => '卡片删除成功。',
        'id' => $id // 返回被删除卡片的 ID
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => '删除卡片失败: ' . $stmt->error
    ]);
}

$stmt->close();
?>