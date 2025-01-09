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
$cardtext = $_POST['cardtext'];
$cardscript = $_POST['cardscript'];
$project_url = $_POST['project_url'] ?? ''; // 如果未提供项目地址，默认为空
$position = $_POST['position'];

// 获取当前最大位置值
$sql = "SELECT MAX(position) AS max_position FROM cards";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$max_position = $row['max_position'];
$new_position = $max_position + 1;

// 插入新卡片
$sql = "INSERT INTO cards (cardtext, cardscript, project_url, position) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $cardtext, $cardscript, $project_url, $new_position);

if ($stmt->execute()) {
    $id = $stmt->insert_id;
    echo json_encode([
        'success' => true,
        'message' => '卡片添加成功。',
        'id' => $id,
        'position' => $new_position,
        'cardtext' => $cardtext,
        'cardscript' => $cardscript,
        'project_url' => $project_url
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => '添加卡片失败: ' . $stmt->error
    ]);
}

$stmt->close();
?>