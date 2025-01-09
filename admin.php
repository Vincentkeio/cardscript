<?php
session_start();
include 'db.php';

// 检查用户是否已登录
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: login.php");
    exit;
}

// 获取当前的 screen 值
$screen = '';
$stmt = $conn->prepare("SELECT screen FROM settings LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $screen = $row['screen'];
}
$stmt->close();

// 获取当前的主标题
$maintitle = 'VPS一键脚本大全'; // 默认值
$maintitle_query = $conn->query("SELECT maintitle FROM cards LIMIT 1");
if ($maintitle_query && $maintitle_query->num_rows > 0) {
    $maintitle_data = $maintitle_query->fetch_assoc();
    $maintitle = $maintitle_data['maintitle'];
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员面板</title>
        <!-- 添加 favicon -->
    <link rel="icon" type="image/x-icon" href="logo.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- 自定义 CSS -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
        }
        .btn {
            margin-right: 10px;
        }
        .table {
            margin-top: 30px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .modal-content {
            border-radius: 10px;
        }
        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0;
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">管理员面板</h2>
    <a href="index.php" class="btn btn-info" title="返回首页"><i class="fas fa-home"></i> 返回首页</a>
    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#settingsModal"><i class="fas fa-cog"></i> 设置</button>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCardModal"><i class="fas fa-plus"></i> 添加卡片</button>

    <h3 class="mt-5">现有卡片</h3>
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>位置</th>
                <th>卡片文本</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM cards ORDER BY position ASC";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                echo "<tr data-id='" . $row['id'] . "'>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['position']) . "</td>";
                echo "<td>" . htmlspecialchars($row['cardtext']) . "</td>";
                echo "<td>
                        <button type='button' class='btn btn-sm btn-primary edit-btn' data-bs-toggle='modal' data-bs-target='#editCardModal' data-id='" . $row['id'] . "' data-text='" . htmlspecialchars($row['cardtext']) . "' data-script='" . htmlspecialchars($row['cardscript']) . "' data-url='" . htmlspecialchars($row['project_url'] ?? '') . "' data-position='" . htmlspecialchars($row['position']) . "'><i class='fas fa-edit'></i> 编辑</button>
                        <button type='button' class='btn btn-sm btn-danger delete-btn' data-id='" . $row['id'] . "'><i class='fas fa-trash'></i> 删除</button>
                      </td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
    <a href="logout.php" class="btn btn-secondary mt-3"><i class='fas fa-sign-out-alt'></i> 退出登录</a>
</div>

<!-- 添加卡片模态框 -->
<div class="modal fade" id="addCardModal" tabindex="-1" aria-labelledby="addCardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCardModalLabel"><i class="fas fa-plus"></i> 添加新卡片</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <form id="addCardForm">
                    <div class="mb-3">
                        <label for="cardtext" class="form-label">卡片文本</label>
                        <input type="text" class="form-control" id="cardtext" name="cardtext" placeholder="请输入卡片文本" required>
                    </div>
                    <div class="mb-3">
                        <label for="cardscript" class="form-label">卡片脚本</label>
                        <textarea class="form-control" id="cardscript" name="cardscript" rows="5" placeholder="请输入卡片脚本" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="project_url" class="form-label">项目地址</label>
                        <input type="text" class="form-control" id="project_url" name="project_url" placeholder="请输入项目地址">
                    </div>
                    <div class="mb-3">
                        <label for="position" class="form-label">位置</label>
                        <input type="number" class="form-control" id="position" name="position" placeholder="请输入位置" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                <button type="button" id="confirmAddCard" class="btn btn-primary">确认添加</button>
            </div>
        </div>
    </div>
</div>

<!-- 编辑卡片模态框 -->
<div class="modal fade" id="editCardModal" tabindex="-1" aria-labelledby="editCardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCardModalLabel"><i class="fas fa-edit"></i> 编辑卡片</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <form id="editCardForm">
                    <input type="hidden" name="id" id="editCardId">
                    <div class="mb-3">
                        <label for="editCardText" class="form-label">卡片文本</label>
                        <input type="text" class="form-control" id="editCardText" name="cardtext" placeholder="请输入卡片文本" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCardScript" class="form-label">卡片脚本</label>
                        <textarea class="form-control" id="editCardScript" name="cardscript" rows="5" placeholder="请输入卡片脚本" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editProjectUrl" class="form-label">项目地址</label>
                        <input type="text" class="form-control" id="editProjectUrl" name="project_url" placeholder="请输入项目地址">
                    </div>
                    <div class="mb-3">
                        <label for="editCardPosition" class="form-label">位置</label>
                        <input type="number" class="form-control" id="editCardPosition" name="position" placeholder="请输入位置" required>
                    </div>
                    <button type="submit" class="btn btn-primary">保存更改</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<!-- 设置模态框 -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="settingsModalLabel"><i class="fas fa-cog"></i> 设置</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="credentials-tab" data-bs-toggle="tab" data-bs-target="#credentials" type="button" role="tab" aria-controls="credentials" aria-selected="true">管理员凭据</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="wallpaper-tab" data-bs-toggle="tab" data-bs-target="#wallpaper" type="button" role="tab" aria-controls="wallpaper" aria-selected="false">壁纸设置</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="maintitle-tab" data-bs-toggle="tab" data-bs-target="#maintitle" type="button" role="tab" aria-controls="maintitle" aria-selected="false">主标题设置</button>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="settingsTabsContent">
                    <div class="tab-pane fade show active" id="credentials" role="tabpanel" aria-labelledby="credentials-tab">
                        <form id="credentialsForm">
                            <div class="mb-3">
                                <label for="current_username" class="form-label">当前用户名</label>
                                <input type="text" class="form-control" id="current_username" name="current_username" placeholder="请输入当前用户名" required>
                            </div>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">当前密码</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" placeholder="请输入当前密码" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_username" class="form-label">新用户名</label>
                                <input type="text" class="form-control" id="new_username" name="new_username" placeholder="请输入新用户名" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">新密码</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="请输入新密码" required>
                            </div>
                            <button type="submit" class="btn btn-primary">保存更改</button>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="wallpaper" role="tabpanel" aria-labelledby="wallpaper-tab">
                        <form id="wallpaperForm">
                            <div class="mb-3">
                                <label for="screen" class="form-label">壁纸 URL</label>
                                <input type="text" class="form-control" id="screen" name="screen" placeholder="请输入壁纸 URL" value="<?php echo htmlspecialchars($screen); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">保存更改</button>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="maintitle" role="tabpanel" aria-labelledby="maintitle-tab">
                        <form id="maintitleForm">
                            <div class="mb-3">
                                <label for="maintitle" class="form-label">主标题</label>
                                <input type="text" class="form-control" id="maintitle" name="maintitle" placeholder="请输入主标题" value="<?php echo htmlspecialchars($maintitle); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">保存更改</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<!-- 删除卡片确认模态框 -->
<div class="modal fade" id="deleteCardModal" tabindex="-1" aria-labelledby="deleteCardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCardModalLabel"><i class="fas fa-trash"></i> 删除卡片</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <p>确定要删除该卡片吗？</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" id="confirmDelete" class="btn btn-danger">删除</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS 和 jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// JavaScript 代码用于模态框操作和 AJAX 请求
$(document).ready(function() {
    // 添加卡片逻辑
    $('#confirmAddCard').click(function() {
        $.ajax({
            url: 'add_card.php',
            type: 'POST',
            data: $('#addCardForm').serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addCardModal').modal('hide');
                    $('#addCardForm')[0].reset();
                    location.reload(); // 刷新页面以更新表格
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                alert('请求失败，请稍后再试。');
            }
        });
    });

    // 编辑卡片逻辑
    $('#editCardModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var text = button.data('text');
        var script = button.data('script');
        var url = button.data('url');
        var position = button.data('position');

        $('#editCardId').val(id);
        $('#editCardText').val(text);
        $('#editCardScript').val(script);
        $('#editProjectUrl').val(url);
        $('#editCardPosition').val(position);
    });

    $('#editCardForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'edit_card.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editCardModal').modal('hide');
                    alert(response.message);
                    location.reload(); // 刷新页面以更新表格
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                alert('请求失败，请稍后再试。');
            }
        });
    });

    // 删除卡片逻辑
    $('.delete-btn').click(function() {
        var id = $(this).data('id');
        $('#deleteCardModal').data('id', id).modal('show');
    });

    $('#confirmDelete').click(function() {
        var id = $('#deleteCardModal').data('id');
        $.ajax({
            url: 'delete_card.php',
            type: 'POST',
            data: {id: id},
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#deleteCardModal').modal('hide');
                    alert(response.message);
                    location.reload(); // 刷新页面以更新表格
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                alert('请求失败，请稍后再试。');
            }
        });
    });

    // 修改管理员凭据逻辑
    $('#credentialsForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'update_settings.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                alert('请求失败，请稍后再试。');
            }
        });
    });

    // 修改壁纸设置逻辑
    $('#wallpaperForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'update_settings.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                alert('请求失败，请稍后再试。');
            }
        });
    });

    // 修改主标题逻辑
    $('#maintitleForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'update_settings.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                alert('请求失败，请稍后再试。');
            }
        });
    });
});
</script>
</body>
</html>