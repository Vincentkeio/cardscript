<?php
// 引入 db.php 和 init_page.php
require_once 'db.php';
require_once 'init_page.php';

// 确保 $conn 变量存在且连接成功
if (!isset($conn) || $conn->connect_error) {
    // 如果连接失败，显示初始化页面
    displayDatabaseInitPage();
    exit;
}

// 获取访问者 IP
$visitor_ip = $_SERVER['REMOTE_ADDR'];

// 检查 IP 是否已存在
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM visitor_ips WHERE ip = ?");
if (!$stmt) {
    die("SQL 语句准备失败: " . $conn->error);
}
$stmt->bind_param("s", $visitor_ip);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

// 如果 IP 不存在，插入新记录并更新统计
if ($count == 0) {
    // 插入新 IP 记录
    $stmt = $conn->prepare("INSERT INTO visitor_ips (ip) VALUES (?)");
    if (!$stmt) {
        die("SQL 语句准备失败: " . $conn->error);
    }
    $stmt->bind_param("s", $visitor_ip);
    $stmt->execute();
    $stmt->close();

    // 更新 IP 计数
    $stmt = $conn->prepare("UPDATE stats SET ips = ips + 1");
    if (!$stmt) {
        die("SQL 语句准备失败: " . $conn->error);
    }
    $stmt->execute();
    $stmt->close();
}

// 获取总访问 IP 计数
$ips = 0; // 默认值
$stmt = $conn->prepare("SELECT ips FROM stats LIMIT 1");
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($ips);
    $stmt->fetch();
    $stmt->close();
}

// 查询所有卡片数据
$sql = "SELECT * FROM cards ORDER BY position ASC";
$result = $conn->query($sql);
$cards = [];
while ($row = $result->fetch_assoc()) {
    $cards[] = $row;
}

// 从数据库中获取背景图片 URL
$screen_url = 'screen.png'; // 默认值
$screen_query = $conn->query("SELECT screen FROM settings LIMIT 1");
if ($screen_query && $screen_query->num_rows > 0) {
    $screen_data = $screen_query->fetch_assoc();
    if (!empty($screen_data['screen'])) {
        $screen_url = $screen_data['screen']; // 使用数据库中的值
    }
}

// 获取主标题
$maintitle = 'VPS一键脚本大全'; // 默认值
$maintitle_query = $conn->query("SELECT maintitle FROM cards LIMIT 1");
if ($maintitle_query && $maintitle_query->num_rows > 0) {
    $maintitle_data = $maintitle_query->fetch_assoc();
    $maintitle = $maintitle_data['maintitle'];
}

// 排版设置（每页行数 × 每页列数，优先使用数据库设置，为空时用默认值）
$settings_row = null;
$settings_query = $conn->query("SELECT rows_per_page, cards_per_row FROM settings LIMIT 1");
if ($settings_query && $settings_query->num_rows > 0) {
    $settings_row = $settings_query->fetch_assoc();
}
$cols = (int)($settings_row['cards_per_row'] ?? 0);
$rows = (int)($settings_row['rows_per_page'] ?? 0);
if ($cols < 1 || $cols > 8) { $cols = 4; }  // 每页列数，限制 1-8
if ($rows < 1 || $rows > 8) { $rows = 4; }  // 每页行数，限制 1-8

// 分页逻辑
$isMobile = strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false;
$perPage = $rows * $cols; // 每页卡片数 = 行数 × 列数
$totalCards = count($cards); // 卡片总数
$totalPages = max(1, ceil($totalCards / $perPage)); // 总页数
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($maintitle); ?></title>
       <!-- 添加 favicon -->
    <link rel="icon" type="image/x-icon" href="logo.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- 自定义 CSS -->
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: 'Arial', sans-serif;
            background-image: url('<?php echo htmlspecialchars($screen_url); ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            position: relative;
            flex: 0 0 auto;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            background-color: rgba(0, 0, 0, 0.7);
        }
        .page-title {
            position: static;
            flex: 1;
            text-align: center;
            margin: 0 130px;
            color: #f0f0f0;
            font-size: clamp(1.1em, 2.2vw, 2em);
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .login-btn {
            margin-left: auto;
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            cursor: pointer;
            text-decoration: none; /* 移除下划线 */
        }
        .login-btn img {
            display: block;
            width: 100%;
            height: auto;
        }
        .ip-counter {
            position: fixed;
            top: 22px;
            left: 24px;
            z-index: 1001;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 7px 16px 7px 13px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(14px) saturate(160%);
            -webkit-backdrop-filter: blur(14px) saturate(160%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.16);
            color: rgba(255, 255, 255, 0.92);
            font-size: 13px;
            font-weight: 400;
            letter-spacing: 0.03em;
            line-height: 1;
            animation: ipFadeIn 0.6s ease both;
        }
        .ip-counter .ip-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #4ade80;
            box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.14);
        }
        .ip-counter .ip-label {
            color: rgba(255, 255, 255, 0.62);
            font-weight: 300;
        }
        .ip-counter .ip-value {
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.02em;
        }
        @keyframes ipFadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to   { opacity: 1; transform: none; }
        }
        .footer {
            position: relative;
            flex: 0 0 auto;
            text-align: center;
            font-size: 7pt;
            color: rgba(255, 255, 255, 0.55);
            padding: 9px 0;
            background-color: rgba(0, 0, 0, 0.55);
            letter-spacing: 0.08em;
            z-index: 1000;
        }
        #pages {
            position: relative;
            flex: 1 1 auto;
            min-height: 0;
            width: 100%;
            overflow: hidden;
        }
        .page {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            padding: clamp(14px, 2.8vh, 34px) clamp(10px, 2.5vw, 40px);
            box-sizing: border-box;
            transition: transform 0.5s ease-in-out;
        }
        .card-container {
            background: transparent;
            width: 100%;
            max-width: none;
            height: 100%;
            overflow: visible;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(<?php echo (int)$cols; ?>, minmax(0, 1fr));
            grid-auto-rows: minmax(0, 1fr);
            gap: clamp(8px, 1.4vh, 16px) clamp(8px, 1.2vw, 18px);
            width: 100%;
            height: 100%;
            box-sizing: border-box;
        }
        .card-cell {
            display: flex;
            min-width: 0;
            min-height: 0;
        }
        .card {
            position: relative;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.88) 0%, rgba(255, 255, 255, 0.68) 100%);
            backdrop-filter: blur(18px) saturate(150%);
            -webkit-backdrop-filter: blur(18px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 16px;
            padding: 12px clamp(14px, 1.4vw, 22px);
            transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s ease, background 0.35s ease, border-color 0.35s ease;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.75),
                0 1px 2px rgba(15, 23, 42, 0.04),
                0 10px 28px rgba(15, 23, 42, 0.10);
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: visible;
            z-index: 1;
            container-type: inline-size;
        }
        .card:hover {
            transform: translateY(-4px);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.78) 100%);
            border-color: rgba(255, 255, 255, 0.85);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.9),
                0 2px 4px rgba(15, 23, 42, 0.05),
                0 20px 44px rgba(15, 23, 42, 0.16);
        }
        .card-body {
            display: flex;
            flex-direction: row;
            align-items: center;
            flex: 1 1 auto;
            min-height: 0;
            min-width: 0;
            gap: clamp(6px, 2cqw, 14px);
        }
        .card-title {
            color: #0f172a;
            margin: 0;
            font-size: clamp(0.85em, 1.35vw, 1.25em);
            font-weight: 700;
            letter-spacing: 0.01em;
            line-height: 1.4;
            box-sizing: border-box;
            flex: 1 1 auto;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            text-align: left;
            overflow-wrap: anywhere;
            word-break: normal;
        }
        .card-actions {
            flex: 0 0 auto;
            align-self: stretch;
            display: flex;
            flex-direction: column;
            /* 关键：不要在横轴拉伸按钮，否则按钮被拉成细竖条、aspect-ratio 失效 */
            align-items: center;
            justify-content: center;
            gap: clamp(3px, 1.5cqw, 8px);
            z-index: 2;
        }
        /* 按钮同时受卡片宽高约束：高度不足时随卡片高度缩小，避免溢出到卡片外 */
        .info-icon, .copy-btn {
            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.65);
            border-radius: clamp(6px, 2.5cqw, 11px);
            cursor: pointer;
            color: #475569;
            flex: 1 1 auto;
            aspect-ratio: 1 / 1;
            width: auto;
            min-width: 20px;
            min-height: 20px;
            max-width: min(44px, 13cqw);
            max-height: min(44px, 13cqw);
            font-size: clamp(10px, 5.5cqw, 18px);
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
            transition: all 0.25s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .info-icon:hover, .copy-btn:hover {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.28);
            transform: scale(1.06);
        }
        .info-icon:active, .copy-btn:active {
            transform: scale(0.96);
        }
        .empty-card {
            opacity: 0;
            pointer-events: none;
        }
        .full-info {
            display: none;
            position: fixed;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.96) 0%, rgba(255, 255, 255, 0.88) 100%);
            backdrop-filter: blur(20px) saturate(150%);
            -webkit-backdrop-filter: blur(20px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 16px;
            padding: 16px 18px;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.8),
                0 16px 44px rgba(15, 23, 42, 0.22);
            width: max-content;
            max-width: min(560px, calc(100vw - 24px));
            min-width: 220px;
            font-size: 12.5px;
            line-height: 1.7;
            color: #334155;
            z-index: 99999;
            transition: top 0.25s ease, left 0.25s ease;
        }
        .full-info p {
            margin: 0 0 6px;
        }
        .full-info p:last-child {
            margin-bottom: 0;
        }
        .full-info strong {
            color: #0f172a;
            font-weight: 700;
        }
        .full-info a {
            color: #4f46e5;
            text-decoration: none;
            word-break: break-all;
        }
        .full-info a:hover {
            text-decoration: underline;
        }
        .full-info pre {
            margin: 8px 0 0;
            padding: 10px 12px;
            background: rgba(15, 23, 42, 0.05);
            border: 1px solid rgba(15, 23, 42, 0.07);
            border-radius: 10px;
            max-width: 100%;
            overflow-x: auto;
            font-size: 11.5px;
            line-height: 1.6;
            color: #1e293b;
        }
        .copy-alert {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            opacity: 1;
            transition: opacity 0.3s;
            z-index: 9999;
            pointer-events: none;
        }
        .pagination {
            position: relative;
            flex: 0 0 auto;
            display: flex;
            justify-content: center;
            margin: 0 0 clamp(8px, 1.6vh, 18px);
            z-index: 1000;
        }
        .pagination-indicators {
            display: flex;
            gap: 10px;
            background-color: rgba(0, 0, 0, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 8px 18px;
            border-radius: 20px;
        }
        .indicator {
            width: 20px;
            height: 4px;
            background-color: rgba(255, 255, 255, 0.4);
            border-radius: 2px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .indicator:hover {
            background-color: rgba(255, 255, 255, 0.7);
        }
        .indicator.active {
            background-color: #ffffff;
        }
        @media (max-width: 767px) {
            .ip-counter {
                display: none;
            }
            .page-title {
                margin: 0 56px;
            }
            body {
                height: auto;
                min-height: 100vh;
                overflow: auto;
            }
            #pages {
                flex: 1 1 auto;
                overflow: visible;
            }
            .page {
                position: relative;
                display: none;
                height: auto;
                padding: 10px;
                /* 兜底：即使 JS 失效或旧标签页残留位移，紧凑布局下也不横向偏移 */
                transform: none !important;
            }
            .page.active {
                display: block;
            }
            .card-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                grid-auto-rows: minmax(96px, auto);
                height: auto;
            }
            .card {
                height: 96px;
            }
            /* 移动端按钮随卡片宽度缩放，同时受 96px 卡片高度约束 */
            .info-icon, .copy-btn {
                flex: 1 1 auto;
                width: auto;
                min-width: 20px;
                min-height: 20px;
                max-width: min(34px, 16cqw);
                max-height: min(34px, 16cqw);
                font-size: clamp(10px, 5.5cqw, 15px);
                border-radius: 7px;
            }
            .card-actions {
                gap: 4px;
            }
            .full-info {
                width: 90%;
                max-width: 300px;
            }
            .header {
                display: flex;
                align-items: center;
                padding: 12px 10px;
            }
            .page-title {
                flex-grow: 1;
                text-align: center;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .login-btn {
                margin-left: auto;
                width: 40px;
                height: 40px;
                background-color: rgba(255, 255, 255, 0.8);
                border-radius: 5px;
            }
        }
        /* 中等屏幕自动减少列数，避免卡片过窄 */
        @media (min-width: 768px) and (max-width: 991px) {
            .card-grid {
                grid-template-columns: repeat(<?php echo max(2, min((int)$cols, 3)); ?>, minmax(0, 1fr));
            }
        }
        /* 矮窗口（宽屏但高度不足，如 850x500）：页面改为可滚动，卡片保底高度，按钮限小，
           避免卡片被压扁后按钮溢出到卡片外部 */
        @media (min-width: 768px) and (max-height: 560px) {
            body {
                height: auto;
                min-height: 100vh;
                overflow: auto;
            }
            #pages {
                overflow: visible;
            }
            .page {
                position: relative;
                display: none;
                height: auto;
                /* 兜底：即使 JS 失效或旧标签页残留位移，紧凑布局下也不横向偏移 */
                transform: none !important;
            }
            .page.active {
                display: block;
            }
            .card-grid {
                grid-auto-rows: minmax(96px, auto);
                height: auto;
            }
            .info-icon, .copy-btn {
                flex: 1 1 auto;
                width: auto;
                min-width: 20px;
                min-height: 20px;
                max-width: min(34px, 16cqw);
                max-height: min(34px, 16cqw);
                font-size: clamp(10px, 5.5cqw, 15px);
                border-radius: 7px;
            }
            .card-actions {
                gap: clamp(2px, 1.5cqw, 5px);
            }
        }
        /* 中等宽度桌面窗口收敛列数，保证文字不被挤压 */
        @media (min-width: 992px) and (max-width: 1279px) {
            .card-grid {
                grid-template-columns: repeat(<?php echo max(3, min((int)$cols, 4)); ?>, minmax(0, 1fr));
            }
        }
        /* 超宽屏适度限制内容宽度，保证卡片不过分拉伸 */
        @media (min-width: 1800px) {
            .page {
                padding-left: max(40px, calc((100vw - 1760px) / 2));
                padding-right: max(40px, calc((100vw - 1760px) / 2));
            }
        }
    </style>
</head>
<body>
    <!-- 头部 -->
    <div class="header">
        <div class="ip-counter">
            <span class="ip-dot"></span>
            <span class="ip-label">累计访问 IP</span>
            <span class="ip-value"><?php echo $ips; ?></span>
        </div>
        <div class="page-title"><?php echo htmlspecialchars($maintitle); ?></div>
        <a href="login.php" class="login-btn"><img src="logbt.png" alt="Login"></a>
    </div>

    <!-- 分页内容 -->
    <div id="pages">
        <?php for ($page = 0; $page < $totalPages; $page++): ?>
                <div class="page<?php echo $page === 0 ? ' active' : ''; ?>" data-page="<?php echo $page; ?>" style="transform: translateX(<?php echo $page * 100; ?>%);">
                <div class="card-container">
                    <?php
                    $start = $page * $perPage;
                    $end = min($start + $perPage, $totalCards);
                    ?>
                    <div class="card-grid">
                        <?php for ($idx = 0; $idx < $perPage; $idx++):
                            $i = $start + $idx;
                            if ($idx < $end - $start):
                                $card = $cards[$i];
                                $gridRow = intdiv($idx, 2) + 1;
                                $gridCol = ($idx % 2) + 1;
                            ?>
                                <div class="card-cell">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($card['cardtext']); ?></h5>
                                            <div class="card-actions">
                                                <button class="info-icon" data-fullinfo="#full-info-<?php echo $i; ?>" data-row="<?php echo $gridRow; ?>" data-col="<?php echo $gridCol; ?>">
                                                    <i class="fas fa-info-circle"></i>
                                                </button>
                                                <button class="copy-btn" data-clipboard-text="<?php echo htmlspecialchars($card['cardscript']); ?>">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="card-cell"><div class="card empty-card"></div></div>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- 分页指示器 -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <div class="pagination-indicators">
                <?php for ($i = 0; $i < $totalPages; $i++): ?>
                    <div class="indicator <?php echo $i === 0 ? 'active' : ''; ?>" data-page="<?php echo $i; ?>"></div>
                <?php endfor; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 页尾 -->
    <div class="footer">
        Cardscript v1.0 Powered by VKbeast
    </div>

    <!-- 自定义复制成功提示框 -->
    <div class="copy-alert" style="display: none;"></div>

    <!-- 浮窗内容 -->
    <?php for ($i = 0; $i < $totalCards; $i++): ?>
        <div class="full-info" id="full-info-<?php echo $i; ?>">
            <p><strong>脚本名称:</strong> <?php echo htmlspecialchars($cards[$i]['cardtext']); ?></p>
            <?php if (!empty($cards[$i]['project_url'])): ?>
                <p><strong>项目地址:</strong> <a href="<?php echo htmlspecialchars($cards[$i]['project_url']); ?>" target="_blank"><?php echo htmlspecialchars($cards[$i]['project_url']); ?></a></p>
            <?php endif; ?>
            <p><strong>一键命令:</strong></p>
            <pre><?php echo htmlspecialchars($cards[$i]['cardscript']); ?></pre>
        </div>
    <?php endfor; ?>

    <!-- Bootstrap 5 JS 和 jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 复制命令（原生 clipboard API，不触发 copy 事件）
            // 单一定时器：重复复制时清除上一个隐藏定时器，避免新提示被提前藏掉
            var copyAlertHideTimer = null;

            function showCopyAlert(text, ok) {
                var alertBox = document.querySelector('.copy-alert');
                if (!alertBox) return;

                if (copyAlertHideTimer) {
                    clearTimeout(copyAlertHideTimer);
                    copyAlertHideTimer = null;
                }

                // 重新显示时不做淡入过渡，直接出现
                alertBox.style.transition = 'none';
                alertBox.textContent = ok ? ('一键命令复制成功: ' + text) : '复制失败，请手动选择命令后按 Ctrl+C 复制';
                alertBox.style.display = 'block';
                alertBox.style.opacity = 1;
                void alertBox.offsetWidth; // 强制回流，使上面的样式立即生效
                alertBox.style.transition = 'opacity 0.3s';

                copyAlertHideTimer = setTimeout(function() {
                    alertBox.style.opacity = 0;
                    setTimeout(function() {
                        alertBox.style.display = 'none';
                    }, 300);
                    copyAlertHideTimer = null;
                }, 3000);
            }

            function fallbackCopy(text) {
                var ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                var ok = false;
                try { ok = document.execCommand('copy'); } catch (err) { ok = false; }
                document.body.removeChild(ta);
                showCopyAlert(text, ok);
            }

            // 复制主路径：优先走 ClipboardItem 写入（部分浏览器安全扩展会拦截
            // clipboard.writeText / execCommand 两条旧通道，ClipboardItem 不受影响）
            function copyText(text) {
                try {
                    if (navigator.clipboard && typeof navigator.clipboard.write === 'function' && typeof ClipboardItem === 'function') {
                        var item = new ClipboardItem({
                            'text/plain': new Blob([text], { type: 'text/plain' })
                        });
                        return navigator.clipboard.write([item]).then(function() {
                            return true;
                        }, function() {
                            return false;
                        });
                    }
                } catch (e) { /* 继续降级 */ }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    return navigator.clipboard.writeText(text).then(function() {
                        return true;
                    }, function() {
                        return false;
                    });
                }
                return Promise.resolve(false);
            }

            document.querySelectorAll('.copy-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var text = btn.getAttribute('data-clipboard-text') || '';
                    if (!text) { showCopyAlert('', false); return; }
                    copyText(text).then(function(ok) {
                        if (ok) {
                            showCopyAlert(text, true);
                        } else {
                            fallbackCopy(text);
                        }
                    });
                });
            });

            // 浮窗显示逻辑
            // display:none 时 offsetWidth/Height 为 0，先临时显示以测量真实尺寸
            function measureFullInfo(el) {
                var prevDisplay = el.style.display;
                var prevVisibility = el.style.visibility;
                el.style.visibility = 'hidden';
                el.style.display = 'block';
                var w = el.offsetWidth;
                var h = el.offsetHeight;
                el.style.visibility = prevVisibility;
                el.style.display = prevDisplay;
                return { width: w, height: h };
            }

            function calculatePopupPosition(buttonRect, floatWidth, floatHeight, row, col, isMobile) {
                let top, left;
                const margin = 10;

                // 计算按钮的中心点
                const centerX = buttonRect.left + buttonRect.width / 2;
                const centerY = buttonRect.top + buttonRect.height / 2;

                // 设置浮窗的左上角位置，使其中心对齐按钮中心
                left = centerX - floatWidth / 2;
                top = centerY - floatHeight / 2;

                // 如果是手机端且第二列，浮窗向左平移60%卡片宽度
                if (isMobile && col === 2) {
                    left -= buttonRect.width * 0.6; // 向左平移60%卡片宽度
                }

                // 限制在可视区域内（含 10px 边距）
                if (left < margin) {
                    left = margin;
                }
                if (left + floatWidth > window.innerWidth - margin) {
                    left = window.innerWidth - margin - floatWidth;
                }
                if (left < margin) {
                    left = margin;
                }
                if (top < margin) {
                    top = margin;
                }
                if (top + floatHeight > window.innerHeight - margin) {
                    top = window.innerHeight - margin - floatHeight;
                }
                if (top < margin) {
                    top = margin;
                }

                return { top, left };
            }

            document.querySelectorAll('.info-icon').forEach(function(icon) {
                var fullInfoId = icon.getAttribute('data-fullinfo');
                var fullInfo = document.querySelector(fullInfoId);
                var row = parseInt(icon.getAttribute('data-row')); // 获取行号
                var col = parseInt(icon.getAttribute('data-col')); // 获取列号

                icon.addEventListener('mouseenter', function() {
                    var iconRect = icon.getBoundingClientRect();
                    var size = measureFullInfo(fullInfo);
                    var isMobile = <?php echo $isMobile ? 'true' : 'false'; ?>; // 判断是否为手机端
                    var position = calculatePopupPosition(iconRect, size.width, size.height, row, col, isMobile);
                    fullInfo.style.top = position.top + 'px';
                    fullInfo.style.left = position.left + 'px';
                    fullInfo.style.display = 'block';
                });

                icon.addEventListener('mouseleave', function() {
                    fullInfo.style.display = 'none';
                });

                // 防止浮窗显示时鼠标移动到浮窗上导致隐藏
                fullInfo.addEventListener('mouseenter', function() {
                    fullInfo.style.display = 'block';
                });

                fullInfo.addEventListener('mouseleave', function() {
                    fullInfo.style.display = 'none';
                });

                // 手机端触摸显示浮窗
                icon.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    var iconRect = icon.getBoundingClientRect();
                    var size = measureFullInfo(fullInfo);
                    var position = calculatePopupPosition(iconRect, size.width, size.height, row, col, true);
                    fullInfo.style.top = position.top + 'px';
                    fullInfo.style.left = position.left + 'px';
                    fullInfo.style.display = 'block';
                });

                icon.addEventListener('touchend', function() {
                    setTimeout(function() {
                        fullInfo.style.display = 'none';
                    }, 300); // 延迟隐藏，防止快速点击
                });
            });

            // 分页逻辑
            let currentPage = 0;
            let totalPages = <?php echo $totalPages; ?>;
            const ROWS = <?php echo (int)$rows; ?>;
            const COLS = <?php echo (int)$cols; ?>;
            const pagesEl = document.getElementById('pages');
            const indicatorsEl = document.querySelector('.pagination-indicators');
            const paginationEl = document.querySelector('.pagination');

            // 紧凑布局：窄屏或矮窗口（页面可滚动、卡片保底高度），与 CSS 断点保持一致
            // 注意：拆成多次单个 matchMedia 调用，避免个别浏览器对逗号媒体查询列表支持不佳
            function isMobileLayout() {
                return window.matchMedia('(max-width: 767px)').matches
                    || window.matchMedia('(max-height: 560px)').matches;
            }

            // 当前生效的列数，与 CSS 断点保持一致
            function currentCols() {
                if (window.matchMedia('(max-width: 767px)').matches) return 2;
                if (window.matchMedia('(min-width: 1280px)').matches) return COLS;
                if (window.matchMedia('(min-width: 992px)').matches) return Math.min(COLS, 4);
                return Math.min(COLS, 3);
            }

            // ========== 响应式重分页 ==========
            // 桌面布局沿用服务端分页（每页 rows×cols 张）；紧凑布局下列数变少，
            // 每页改放 rows×当前列数 张，避免 25 张卡在 2 列下铺成 13 行。
            var desktopSnapshot = null; // 桌面布局每页格子节点备份（切回桌面时恢复）
            var layoutSig = null;       // 当前布局签名，未变化则不重建

            function snapshotDesktop() {
                desktopSnapshot = [];
                pagesEl.querySelectorAll('.page').forEach(function(pg) {
                    desktopSnapshot.push(Array.prototype.slice.call(pg.querySelectorAll('.card-cell')));
                });
            }

            function makePageEl() {
                var pg = document.createElement('div');
                pg.className = 'page';
                pg.setAttribute('data-page', '0');
                pg.innerHTML = '<div class="card-container"><div class="card-grid"></div></div>';
                return pg;
            }

            function makeEmptyCell() {
                var cell = document.createElement('div');
                cell.className = 'card-cell';
                cell.innerHTML = '<div class="card empty-card"></div>';
                return cell;
            }

            function fillPage(pgEl, cellNodes, perView) {
                var grid = pgEl.querySelector('.card-grid');
                while (grid.firstChild) grid.removeChild(grid.firstChild);
                for (var i = 0; i < perView; i++) {
                    grid.appendChild(i < cellNodes.length ? cellNodes[i] : makeEmptyCell());
                }
            }

            function collectRealCells() {
                var real = [];
                pagesEl.querySelectorAll('.card-cell').forEach(function(c) {
                    if (!c.querySelector('.empty-card')) real.push(c);
                });
                return real;
            }

            function applyLayout(perView, pageLists) {
                var need = Math.max(1, pageLists.length);
                var pageEls = Array.prototype.slice.call(pagesEl.querySelectorAll('.page'));
                while (pageEls.length > need) {
                    pagesEl.removeChild(pageEls.pop());
                }
                while (pageEls.length < need) {
                    var pg = makePageEl();
                    pagesEl.appendChild(pg);
                    pageEls.push(pg);
                }
                for (var i = 0; i < need; i++) {
                    fillPage(pageEls[i], pageLists[i], perView);
                    pageEls[i].setAttribute('data-page', i);
                }
                totalPages = need;
                rebuildIndicators();
            }

            function applyCompact() {
                var perView = ROWS * currentCols();
                var real = collectRealCells();
                var lists = [];
                for (var i = 0; i < real.length; i += perView) {
                    lists.push(real.slice(i, i + perView));
                }
                if (!lists.length) lists.push([]);
                applyLayout(perView, lists);
            }

            function restoreDesktop() {
                applyLayout(ROWS * COLS, desktopSnapshot);
            }

            function rebuildIndicators() {
                if (!indicatorsEl) return;
                while (indicatorsEl.firstChild) indicatorsEl.removeChild(indicatorsEl.firstChild);
                for (var i = 0; i < totalPages; i++) {
                    var d = document.createElement('div');
                    d.className = 'indicator' + (i === currentPage ? ' active' : '');
                    d.setAttribute('data-page', i);
                    indicatorsEl.appendChild(d);
                }
                // 只有一页时隐藏分页栏
                if (paginationEl) {
                    paginationEl.style.display = totalPages > 1 ? '' : 'none';
                }
            }

            function updatePagination() {
                var inds = indicatorsEl ? indicatorsEl.querySelectorAll('.indicator') : [];
                inds.forEach(function(indicator, index) {
                    if (index === currentPage) {
                        indicator.classList.add('active');
                    } else {
                        indicator.classList.remove('active');
                    }
                });
                // 桌面端：所有页绝对定位叠放，用 transform 横向滑动，active 类不参与显示
                // 紧凑布局：仅当前页 display:block；CSS 已将 transform 置 none，旧位移不会残留
                pagesEl.querySelectorAll('.page').forEach(function(page, index) {
                    if (isMobileLayout()) {
                        if (index === currentPage) {
                            page.classList.add('active');
                        } else {
                            page.classList.remove('active');
                        }
                    } else {
                        page.style.transform = `translateX(${(index - currentPage) * 100}%)`;
                    }
                });
            }

            function syncLayout() {
                var sig = isMobileLayout() ? ('compact:' + currentCols()) : 'desktop';
                if (sig !== layoutSig) {
                    layoutSig = sig;
                    if (sig === 'desktop') {
                        restoreDesktop();
                    } else {
                        applyCompact();
                    }
                    if (currentPage > totalPages - 1) {
                        currentPage = Math.max(0, totalPages - 1);
                    }
                }
                updatePagination();
            }

            // 监听分页指示器点击（事件委托，重建指示器后依然有效）
            if (paginationEl) {
                paginationEl.addEventListener('click', function(e) {
                    var target = e.target;
                    while (target && target !== paginationEl &&
                           !(target.classList && target.classList.contains('indicator'))) {
                        target = target.parentNode;
                    }
                    if (!target || target === paginationEl) return;
                    var p = parseInt(target.getAttribute('data-page'), 10);
                    if (!isNaN(p)) {
                        currentPage = p;
                        updatePagination();
                    }
                });
            }

            // 电脑端滚轮翻页（紧凑布局下页面自身可滚动，滚轮让位给滚动）
            window.addEventListener('wheel', function(e) {
                if (totalPages <= 1) return; // 如果只有一页，不处理滚动
                if (isMobileLayout()) return; // 紧凑布局：滚轮用于滚动页面

                if (e.deltaY > 0 && currentPage < totalPages - 1) {
                    currentPage++;
                } else if (e.deltaY < 0 && currentPage > 0) {
                    currentPage--;
                }

                updatePagination();
            });

            // 窗口尺寸跨越断档时重新分页并同步布局
            let resizeTimer = null;
            window.addEventListener('resize', function() {
                if (resizeTimer) clearTimeout(resizeTimer);
                resizeTimer = setTimeout(syncLayout, 150);
            });

            // 手机端滑动翻页（事件委托，重建分页后依然有效）
            let startX = 0;
            let isScrolling = false;

            pagesEl.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                isScrolling = false;
            });

            pagesEl.addEventListener('touchmove', function(e) {
                let currentX = e.touches[0].clientX;
                let diff = currentX - startX;
                if (Math.abs(diff) > 10) {
                    isScrolling = true;
                }
            });

            pagesEl.addEventListener('touchend', function(e) {
                if (isScrolling) {
                    if (startX - e.changedTouches[0].clientX > 50) {
                        if (currentPage < totalPages - 1) {
                            currentPage++;
                        }
                    } else if (e.changedTouches[0].clientX - startX > 50) {
                        if (currentPage > 0) {
                            currentPage--;
                        }
                    }
                    updatePagination();
                }
            });

            // 初始化：记录桌面分页，按当前布局重建
            snapshotDesktop();
            syncLayout();
        });
    </script>
</body>
</html>