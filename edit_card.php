<?php
session_start();
include 'db.php';

// 检查管理员是否已登录
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    die(json_encode(['success' => false, 'message' => '未登录。']));
}

// 检查并修复表结构
$expectedColumns = [
    "id" => "int(11)",
    "cardtext" => "varchar(255)",
    "cardscript" => "text",
    "project_url" => "varchar(255)",
    "position" => "int(11)",
    "maintitle" => "varchar(255)"
];
checkAndFixTableStructure($conn, "cards", $expectedColumns);

// 获取表单数据
$id = $_POST['id'];
$cardtext = $_POST['cardtext'];
$cardscript = $_POST['cardscript'];
$project_url = $_POST['project_url'] ?? ''; // 如果未提供项目地址，默认为空
$position = $_POST['position'];

// 更新卡片信息
$sql = "UPDATE cards SET cardtext = ?, cardscript = ?, project_url = ?, position = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssii", $cardtext, $cardscript, $project_url, $position, $id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => '卡片更新成功。',
        'id' => $id,
        'position' => $position,
        'cardtext' => $cardtext,
        'cardscript' => $cardscript,
        'project_url' => $project_url
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => '更新卡片失败: ' . $stmt->error
    ]);
}

$stmt->close();
?>