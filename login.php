<?php
session_start();
include 'db.php';

// 检查是否已经提交登录表单
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 从数据库中获取管理员凭据
    $result = $conn->query("SELECT admin_username, admin_password FROM settings LIMIT 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // 验证用户名和密码
        if ($username === $row['admin_username'] && password_verify($password, $row['admin_password'])) {
            // 登录成功，设置会话并跳转到管理员面板
            $_SESSION['admin_logged_in'] = true;
            header("Location: admin.php");
            exit;
        } else {
            // 用户名或密码错误
            $error = "用户名或密码错误。";
        }
    } else {
        // 如果没有找到管理员凭据，提示错误
        $error = "管理员凭据未设置。";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录</title>
       <!-- 添加 favicon -->
    <link rel="icon" type="image/x-icon" href="logo.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义样式 -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Nunito', sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group label {
            font-weight: bold;
        }
        .btn-primary {
            width: 100%;
            padding: 10px;
            font-size: 16px;
        }
        .alert {
            margin-top: 15px;
        }
    </style>
</head>
<body>
<div class="login-container">
    <h2>登录</h2>
    <?php if (isset($error)): ?>
        <!-- 如果登录失败，显示错误信息 -->
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label for="username" class="form-label">用户名:</label>
            <input type="text" name="username" id="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">密码:</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">登录</button>
    </form>
</div>
<!-- Bootstrap 5 JS 和依赖 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>