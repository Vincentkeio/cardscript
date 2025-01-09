<?php
// init_page.php

// 生成随机密码（仅包含数字和大小写字母）
function generateRandomPassword($length = 12) {
    // 定义允许的字符集（数字和大小写字母）
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

// 生成配置文件
function generateConfigFile($password) {
    // 默认数据库名和用户名
    $dbname = "cardscriptcopy";
    $username = "cardscriptcopy";

    // 保存配置到 config.php
    $configContent = <<<PHP
<?php
// 数据库配置
\$servername = "localhost";
\$username = "$username";
\$password = "$password";
\$dbname = "$dbname";
?>
PHP;

    file_put_contents('config.php', $configContent);
    // 设置文件权限为 600（只有所有者可以读写）
    if (file_exists('config.php')) {
        chmod('config.php', 0600);
    }
}

// 显示数据库初始化页面
function displayDatabaseInitPage() {
    // 默认数据库名和用户名
    $dbname = "cardscriptcopy";
    $username = "cardscriptcopy";

    // 如果 config.php 存在，从中读取配置
    if (file_exists('config.php')) {
        require_once 'config.php';
        // 如果配置文件中已经定义了密码，使用配置文件中的密码
        if (isset($password) && !empty($password)) {
            // 使用配置文件中的密码
            $password = $password;
        } else {
            // 如果配置文件中没有密码，生成随机密码并写入配置文件
            $password = generateRandomPassword();
            generateConfigFile($password); // 将生成的密码写入配置文件
        }
    } else {
        // 如果 config.php 不存在，生成随机密码并写入配置文件
        $password = generateRandomPassword();
        generateConfigFile($password); // 将生成的密码写入配置文件
    }

    // 显示初始化页面
    echo <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库初始化</title>
        <!-- 添加 favicon -->
    <link rel="icon" type="image/x-icon" href="logo.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .container h2 {
            color: #d9534f;
        }
        .container p {
            margin: 10px 0;
        }
        .container pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            text-align: left;
        }
        .container a {
            color: #5cb85c;
            text-decoration: none;
        }
        .container a:hover {
            text-decoration: underline;
        }
        .note {
            color: #d9534f;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>数据库初始化</h2>
        <p>数据库连接失败，请手动创建数据库并完成初始化。</p>
        <p>默认数据库名: <strong>$dbname</strong></p>
        <p>默认用户名: <strong>$username</strong></p>
        <p>密码: <strong>$password</strong></p>
        <p><strong>请确保数据库使用以下字符编码：</strong></p>
        <ul>
            <li>字符集：<code>utf8mb4</code></li>
        </ul>
        <p>请使用以下 SQL 命令创建数据库和用户：</p>
        <pre>
-- 创建数据库，并指定字符集
CREATE DATABASE `$dbname` CHARACTER SET utf8mb4;

-- 创建用户
CREATE USER '$username'@'localhost' IDENTIFIED BY '$password';

-- 授予用户权限
GRANT ALL PRIVILEGES ON `$dbname`.* TO '$username'@'localhost';

-- 刷新权限
FLUSH PRIVILEGES;
        </pre>
        <p>完成后，<a href="index.php">点击此处刷新页面</a>。</p>
        <div class="note">
            <p>网站默认管理员用户名和密码均为 <strong>sysadmin</strong>，请登录后及时修改。</p>
        </div>
    </div>
</body>
</html>
HTML;
    exit;
}
?>