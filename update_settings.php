<?php
session_start();
include 'db.php';

// 检查用户是否已登录
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    die(json_encode(['success' => false, 'message' => '未登录。']));
}

// 处理管理员凭据更新
if (isset($_POST['current_username'])) {
    $currentUsername = $_POST['current_username'];
    $currentPassword = $_POST['current_password'];
    $newUsername = $_POST['new_username'];
    $newPassword = $_POST['new_password'];

    // 从数据库中获取当前的管理员凭据
    $result = $conn->query("SELECT id, admin_username, admin_password FROM settings LIMIT 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $settings_id = $row['id']; // 获取当前记录的 id

        // 验证当前用户名和密码
        if ($currentUsername === $row['admin_username'] && password_verify($currentPassword, $row['admin_password'])) {
            // 加密新密码
            $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // 使用预处理语句更新凭据
            $sql = "UPDATE settings SET admin_username = ?, admin_password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $newUsername, $hashedNewPassword, $settings_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => '管理员凭据已更新。']);
            } else {
                echo json_encode(['success' => false, 'message' => '更新凭据失败: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => '当前用户名或密码错误。']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '管理员凭据未设置。']);
    }
}

// 处理壁纸 URL 更新
if (isset($_POST['screen'])) {
    $screen = $_POST['screen'];

    // 获取当前记录的 id
    $result = $conn->query("SELECT id FROM settings LIMIT 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $settings_id = $row['id'];

        // 使用预处理语句更新壁纸 URL
        $sql = "UPDATE settings SET screen = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $screen, $settings_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => '壁纸 URL 已更新。']);
        } else {
            echo json_encode(['success' => false, 'message' => '更新壁纸 URL 失败: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => '设置记录不存在。']);
    }
}

// 处理主标题更新
if (isset($_POST['maintitle'])) {
    $maintitle = $_POST['maintitle'];

    // 使用预处理语句更新主标题
    $sql = "UPDATE cards SET maintitle = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $maintitle);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => '主标题已更新。']);
    } else {
        echo json_encode(['success' => false, 'message' => '更新主标题失败: ' . $stmt->error]);
    }
    $stmt->close();
}
?>