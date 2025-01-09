<?php
// 开启错误显示
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 引入初始化页面功能
require_once 'init_page.php';

// 检查配置文件是否存在
if (!file_exists('config.php')) {
    // 配置文件不存在，生成默认配置并写入 config.php
    generateConfigFile();
    // 显示初始化页面
    displayDatabaseInitPage();
    exit;
}

// 引入配置文件
require_once 'config.php';

// 尝试连接 MySQL 服务器
try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    // 检查连接是否成功
    if ($conn->connect_error) {
        // 连接失败，显示初始化页面
        displayDatabaseInitPage();
        exit;
    }

    // 设置字符集
    $conn->set_charset("utf8mb4");

    // 尝试选择数据库
    if (!$conn->select_db($dbname)) {
        // 数据库不存在，显示初始化页面
        displayDatabaseInitPage();
        exit;
    }
} catch (mysqli_sql_exception $e) {
    // 捕获异常（如用户名或密码错误），显示初始化页面
    displayDatabaseInitPage();
    exit;
}

/**
 * 检查并修复表结构
 * @param mysqli $conn 数据库连接对象
 * @param string $table 表名
 * @param array $expectedColumns 期望的列结构
 */
function checkAndFixTableStructure($conn, $table, $expectedColumns) {
    // 检查表是否存在
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        // 表不存在，创建新表
        createTable($conn, $table);
        return;
    }

    // 获取实际的列结构
    $actualColumns = [];
    $result = $conn->query("DESCRIBE $table");
    while ($row = $result->fetch_assoc()) {
        $actualColumns[strtolower($row['Field'])] = $row['Type'];
    }

    // 检查每个期望的列是否存在，若不存在则添加
    foreach ($expectedColumns as $column => $type) {
        if (!isset($actualColumns[strtolower($column)]) || $actualColumns[strtolower($column)] != $type) {
            // 列不存在或类型不匹配，添加列
            $sql = "ALTER TABLE $table ADD $column $type";
            if (!$conn->query($sql)) {
                echo "添加列 $column 到表 $table 失败: " . $conn->error . "<br>";
            } else {
                echo "添加列 $column 到表 $table 成功<br>";
            }
        }
    }
}

/**
 * 创建表
 * @param mysqli $conn 数据库连接对象
 * @param string $table 表名
 */
function createTable($conn, $table) {
    switch ($table) {
        case "cards":
            $sql = "CREATE TABLE cards (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cardtext VARCHAR(255),
                cardscript TEXT,
                project_url VARCHAR(255),
                position INT,
                maintitle VARCHAR(255) DEFAULT 'VPS一键脚本大全'
            ) DEFAULT CHARSET=utf8mb4;";
            break;
        case "settings":
            $sql = "CREATE TABLE settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pages INT,
                rows_per_page INT,
                cards_per_row INT,
                admin_username VARCHAR(255) DEFAULT '',
                admin_password VARCHAR(255) DEFAULT '',
                screen VARCHAR(255) DEFAULT 'screen.png'
            ) DEFAULT CHARSET=utf8mb4;";
            break;
        case "visitor_ips":
            $sql = "CREATE TABLE visitor_ips (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip VARCHAR(45) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) DEFAULT CHARSET=utf8mb4;";
            break;
        case "stats":
            $sql = "CREATE TABLE stats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ips INT DEFAULT 0
            ) DEFAULT CHARSET=utf8mb4;";
            break;
        default:
            die("未知表名: $table");
    }

    if ($conn->query($sql) !== TRUE) {
        die("创建表 <strong>$table</strong> 失败: " . $conn->error);
    }
}

// 定义期望的表结构
$expectedTables = [
    "cards" => [
        "id" => "int(11)",
        "cardtext" => "varchar(255)",
        "cardscript" => "text",
        "project_url" => "varchar(255)",
        "position" => "int(11)",
        "maintitle" => "varchar(255)"
    ],
    "settings" => [
        "id" => "int(11)",
        "pages" => "int(11)",
        "rows_per_page" => "int(11)",
        "cards_per_row" => "int(11)",
        "admin_username" => "varchar(255)",
        "admin_password" => "varchar(255)",
        "screen" => "varchar(255)"
    ],
    "visitor_ips" => [
        "id" => "int(11)",
        "ip" => "varchar(45)",
        "created_at" => "timestamp"
    ],
    "stats" => [
        "id" => "int(11)",
        "ips" => "int(11)"
    ]
];

// 检查并修复表结构
foreach ($expectedTables as $table => $expectedColumns) {
    checkAndFixTableStructure($conn, $table, $expectedColumns);
}

// 检查 settings 表中是否有数据，如果没有则插入默认的管理员凭据
$result = $conn->query("SELECT COUNT(*) AS count FROM settings");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        // 如果 settings 表中没有数据，插入默认的管理员凭据
        $defaultUsername = 'sysadmin';
        $defaultPassword = password_hash('sysadmin', PASSWORD_DEFAULT); // 加密密码
        $conn->query("INSERT INTO settings (admin_username, admin_password) VALUES ('$defaultUsername', '$defaultPassword')");
    }
} else {
    // 如果 settings 表中没有数据，插入默认的管理员凭据
    $defaultUsername = 'sysadmin';
    $defaultPassword = password_hash('sysadmin', PASSWORD_DEFAULT); // 加密密码
    $conn->query("INSERT INTO settings (admin_username, admin_password) VALUES ('$defaultUsername', '$defaultPassword')");
}

// 检查 stats 表中是否有数据，如果没有则插入初始记录
$result = $conn->query("SELECT COUNT(*) AS count FROM stats");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        // 如果 stats 表中没有数据，插入初始记录
        $conn->query("INSERT INTO stats (ips) VALUES (0)");
    }
} else {
    // 如果 stats 表中没有数据，插入初始记录
    $conn->query("INSERT INTO stats (ips) VALUES (0)");
}
?>