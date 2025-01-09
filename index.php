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

// 分页逻辑
$isMobile = strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false;
$perPage = $isMobile ? 8 : 16; // 手机端每页显示 8 张卡片，PC 端显示 16 张
$totalCards = count($cards); // 卡片总数
$totalPages = ceil($totalCards / $perPage); // 总页数
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
        }
        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.7);
        }
        .page-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            color: #f0f0f0;
            font-size: 2em;
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
            top: 20px;
            left: 20px;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 16px;
            color: #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: slideInLeft 1s ease-in-out, glow 2s infinite alternate;
            z-index: 1001;
        }
        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes glow {
            from {
                box-shadow: 0 0 10px rgba(106, 17, 203, 0.7), 0 0 20px rgba(37, 117, 252, 0.7);
            }
            to {
                box-shadow: 0 0 20px rgba(106, 17, 203, 0.9), 0 0 30px rgba(37, 117, 252, 0.9);
            }
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 7pt;
            color: #ccc;
            padding: 10px 0;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
        }
        #pages {
            position: relative;
            top: 100px;
            width: 100%;
            height: calc(100vh - 100px);
            overflow: hidden;
        }
        .page {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            transition: transform 0.5s ease-in-out;
        }
        .card-container {
            background: transparent;
            padding: 10px;
            width: 90%;
            max-width: 1200px;
            height: 100%;
            overflow: visible;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-left: -10px !important;
            margin-right: -10px !important;
            margin-bottom: 10px !important;
        }
        .col-md-3, .col-6 {
            width: 25%;
            box-sizing: border-box;
            padding: 0 10px !important;
        }
        .card {
            position: relative;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: visible;
            margin-bottom: 10px !important;
            z-index: 1;
        }
        .card-body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }
        .card-title {
            color: #333333;
            margin: 0;
            font-size: 1.2em;
            font-weight: bold;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            text-align: left;
            padding-left: 5px;
            width: calc(100% - 2em);
            word-break: break-all;
        }
        .card-actions {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            gap: 5px;
            z-index: 2;
        }
        .info-icon, .copy-btn {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 5px;
            cursor: pointer;
            font-size: 1.2em;
            color: #333;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .empty-card {
            opacity: 0;
            pointer-events: none;
        }
        .full-info {
            display: none;
            position: fixed;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            border-radius: 5px;
            width: 100%;
            max-width: 300px;
            min-width: 200px;
            word-wrap: break-word;
            font-size: 12px;
            z-index: 99999;
            transition: top 0.3s, left 0.3s;
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
            position: fixed;
            bottom: 80px;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: center;
            z-index: 1000;
        }
        .pagination-indicators {
            display: flex;
            gap: 10px;
            background-color: rgba(0, 0, 0, 0.6);
            padding: 10px 20px;
            border-radius: 20px;
        }
        .indicator {
            width: 20px;
            height: 4px;
            background-color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .indicator.active {
            background-color: #2575fc;
            transform: scale(1.2);
        }
        @media (max-width: 767px) {
            .ip-counter {
                display: none;
            }
            .col-md-3 {
                width: 50%;
            }
            .card-container {
                margin-top: 20px;
                overflow: visible;
            }
            #pages {
                height: calc(100vh - 60px);
            }
            .page {
                height: auto;
            }
            .row {
                margin-bottom: 0 !important;
            }
            body {
                overflow: auto;
            }
            .card {
                height: 100px;
            }
            .full-info {
                width: 90%;
                max-width: 300px;
            }
            .header {
                display: flex;
                align-items: center;
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
    </style>
</head>
<body>
    <!-- 头部 -->
    <div class="header">
        <div class="ip-counter">本站累计访问IP：<?php echo $ips; ?></div>
        <div class="page-title"><?php echo htmlspecialchars($maintitle); ?></div>
        <a href="login.php" class="login-btn"><img src="logbt.png" alt="Login"></a>
    </div>

    <!-- 分页内容 -->
    <div id="pages">
        <?php for ($page = 0; $page < $totalPages; $page++): ?>
            <div class="page" data-page="<?php echo $page; ?>" style="transform: translateX(<?php echo $page * 100; ?>%);">
                <div class="card-container">
                    <?php
                    $start = $page * $perPage;
                    $end = min($start + $perPage, $totalCards);
                    for ($row = 0; $row < ceil($perPage / ($isMobile ? 2 : 4)); $row++): ?>
                        <div class="row">
                            <?php for ($col = 0; $col < ($isMobile ? 2 : 4); $col++):
                                $i = $start + $row * ($isMobile ? 2 : 4) + $col;
                                if ($i < $end):
                                    $card = $cards[$i];
                                ?>
                                    <div class="col-md-3 col-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($card['cardtext']); ?></h5>
                                                <div class="card-actions">
                                                    <button class="info-icon" data-fullinfo="#full-info-<?php echo $i; ?>" data-row="<?php echo $row + 1; ?>" data-col="<?php echo $col + 1; ?>">
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
                                    <div class="col-md-3 col-6">
                                        <div class="card empty-card"></div>
                                    </div>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    <?php endfor; ?>
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
        Cardscript v0 Powered by VKbeast
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化 ClipboardJS
            new ClipboardJS('.copy-btn').on('success', function(e) {
                var alertBox = document.querySelector('.copy-alert');
                alertBox.textContent = '一键命令复制成功: ' + e.text;
                alertBox.style.display = 'block';
                setTimeout(function() {
                    alertBox.style.opacity = 0;
                    setTimeout(function() {
                        alertBox.style.display = 'none';
                        alertBox.style.opacity = 1;
                    }, 300);
                }, 3000);
            });

            // 浮窗显示逻辑
            function calculatePopupPosition(buttonRect, floatWidth, floatHeight, row, col, isMobile) {
                let top, left;

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

                // 检查是否超出屏幕左侧
                if (left < 0) {
                    left = 0;
                }
                // 检查是否超出屏幕右侧
                if (left + floatWidth > window.innerWidth) {
                    left = window.innerWidth - floatWidth;
                }
                // 检查是否超出屏幕顶部
                if (top < 0) {
                    top = 0;
                }
                // 检查是否超出屏幕底部
                if (top + floatHeight > window.innerHeight) {
                    top = window.innerHeight - floatHeight;
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
                    var floatWidth = fullInfo.offsetWidth;
                    var floatHeight = fullInfo.offsetHeight;
                    var isMobile = <?php echo $isMobile ? 'true' : 'false'; ?>; // 判断是否为手机端
                    var position = calculatePopupPosition(iconRect, floatWidth, floatHeight, row, col, isMobile);
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
                    fullInfo.style.display = 'block';
                    var iconRect = icon.getBoundingClientRect();
                    var floatWidth = fullInfo.offsetWidth;
                    var floatHeight = fullInfo.offsetHeight;
                    var position = calculatePopupPosition(iconRect, floatWidth, floatHeight, row, col, true);
                    fullInfo.style.top = position.top + 'px';
                    fullInfo.style.left = position.left + 'px';
                });

                icon.addEventListener('touchend', function() {
                    setTimeout(function() {
                        fullInfo.style.display = 'none';
                    }, 300); // 延迟隐藏，防止快速点击
                });
            });

            // 分页逻辑
            let currentPage = 0;
            const totalPages = <?php echo $totalPages; ?>;
            const pages = document.querySelectorAll('.page');
            const indicators = document.querySelectorAll('.indicator');

            // 更新分页指示器样式
            function updatePagination() {
                indicators.forEach(function(indicator, index) {
                    if (index === currentPage) {
                        indicator.classList.add('active');
                    } else {
                        indicator.classList.remove('active');
                    }
                });
                // 更新页面位置
                pages.forEach(function(page, index) {
                    page.style.transform = `translateX(${(index - currentPage) * 100}%)`;
                });
            }

            // 监听分页指示器点击事件
            indicators.forEach(function(indicator) {
                indicator.addEventListener('click', function() {
                    currentPage = parseInt(this.getAttribute('data-page'));
                    updatePagination();
                });
            });

            // 电脑端滚轮翻页
            window.addEventListener('wheel', function(e) {
                if (totalPages <= 1) return; // 如果只有一页，不处理滚动

                if (e.deltaY > 0 && currentPage < totalPages - 1) {
                    currentPage++;
                } else if (e.deltaY < 0 && currentPage > 0) {
                    currentPage--;
                }

                updatePagination();
            });

            // 手机端滑动翻页
            let startX = 0;
            let isScrolling = false;

            pages.forEach(function(page) {
                page.addEventListener('touchstart', function(e) {
                    startX = e.touches[0].clientX;
                    isScrolling = false;
                });

                page.addEventListener('touchmove', function(e) {
                    let currentX = e.touches[0].clientX;
                    let diff = currentX - startX;
                    if (Math.abs(diff) > 10) {
                        isScrolling = true;
                    }
                });

                page.addEventListener('touchend', function(e) {
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
            });
        });
    </script>
</body>
</html>