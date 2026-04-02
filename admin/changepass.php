<?php
require_once "../includes/connectdb.php";

if (!isset($_SESSION["id"])) {
    header("location: ../admin");
    exit;
}

// Categories for Sidebar Standardized from orders.php
$show_protype = "";
$sql_types = "SELECT protype, COUNT(*) as count FROM products GROUP BY protype ORDER BY protype ASC";
$result_types = $link->query($sql_types);
if ($result_types) {
    while ($type_row = $result_types->fetch_assoc()) {
        $show_protype .= '
        <li class="sidebar-category-item d-flex align-items-center justify-content-between">
            <a href="../admin/protype.php?id=' . urlencode($type_row["protype"]) . '" class="flex-grow-1">
                <span>' . htmlspecialchars($type_row["protype"]) . ' (' . $type_row["count"] . ')</span>
            </a>
            <button type="button" class="btn btn-link btn-sm text-danger btndelprotype p-0 ml-2" value="' . htmlspecialchars($type_row["protype"]) . '" title="Xóa danh mục">
                <i class="fa fa-trash"></i>
            </button>
        </li>';
    }
}

// Get total count for stats
$total_res = $link->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc();
$total_records = $total_res['total'];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu | Mẫu CNC Admin</title>
    <link rel="shortcut icon" href="../home/imgs/logo/mt_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="../home/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/admin.css?v=1.5" rel="stylesheet">
    <script src="../home/js/jquery.js"></script>
    <script src="../home/js/bootstrap.min.js"></script>
    <script>
        function checkPasswords() {
            var x = document.getElementById("password2");
            var y = document.getElementById("password1");
            var m = document.getElementById("message");
            if (x.value !== "" && y.value !== "") {
                if (x.value != y.value) {
                    m.innerHTML = '<span class="text-danger"><i class="fa fa-times-circle"></i> Mật khẩu không trùng khớp!</span>';
                    document.getElementById("btncp").disabled = true;
                } else {
                    m.innerHTML = '<span class="text-success"><i class="fa fa-check-circle"></i> Mật khẩu trùng khớp</span>';
                    document.getElementById("btncp").disabled = false;
                }
            } else {
                m.innerHTML = "";
            }
        }
    </script>
    <style>
        .password-form-card {
            max-width: 500px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--secondary-slate);
        }

        #btncp {
            width: 100%;
            padding: 12px;
            font-weight: 700;
            border-radius: 10px;
            margin-top: 20px;
            transition: all 0.3s;
        }
    </style>
</head>

<body>

    <aside class="admin-sidebar" id="sidebar">
        <div class="sidebar-content">
            <div class="sidebar-header">
                <img src="../home/imgs/logo/mt_logo.png" alt="Logo" style="height: 40px; margin-bottom: 10px;">
                <h2>MẪU CNC</h2>
                <p style="font-size: 12px; color: #95a5a6; margin: 0;">Admin Portal</p>
            </div>
            
            <div class="sidebar-nav">
                <div class="cache-switch-container">
                    <div class="switch-label">
                        <span>Trạng thái Cache</span>
                        <i class="fa fa-bolt" style="color: <?= $cacheEnabled ? 'var(--accent-emerald)' : '#64748b' ?>"></i>
                    </div>
<?php
require_once "../includes/cache.php";
$cacheEnabled = FileCache::isEnabled();
?>
                    <form method="post" action="toggle_cache.php" id="cacheForm">
                        <input type="hidden" name="csrf_token" value="<?php echo Security\generate_csrf_token(); ?>">
                        <label class="toggle-switch">
                            <input type="checkbox" name="cache_toggle" onchange="document.getElementById('cacheForm').submit()" <?= $cacheEnabled ? 'checked' : '' ?>>
                            <span class="slider"><span class="slider-text"></span></span>
                        </label>
                    </form>
                </div>

                <div class="nav-group-title">Menu Chính</div>
                <ul>
                    <li><a href="admin.php"><i class="fa fa-home mr-2"></i> <span>Tổng quan</span></a></li>
                    <li><a href="orders.php"><i class="fa fa-shopping-cart mr-2"></i> <span>Quản lý Đơn hàng</span></a></li>
                    <li><a href="addproduct"><i class="fa fa-plus-circle mr-2"></i> <span>Thêm sản phẩm</span></a></li>
                    <li><a href="changepass.php" class="active"><i class="fa fa-key mr-2"></i> <span>Đổi mật khẩu</span></a></li>
                    <li><a href="logout.php"><i class="fa fa-sign-out mr-2"></i> <span>Đăng xuất</span></a></li>
                </ul>

                <div class="nav-group-title mt-4">Danh mục sản phẩm</div>
                <ul class="category-list">
                    <?php echo $show_protype; ?>
                </ul>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <div>
                <h1 style="font-weight: 700; font-size: 28px; margin: 0;">Bảo mật tài khoản</h1>
                <p class="text-muted">Cập nhật mật khẩu quản trị định kỳ để bảo vệ website</p>
            </div>

            <form action="admin.php" method="GET" class="search-wrapper">
                <i class="fa fa-search"></i>
                <input type="text" name="search" placeholder="Tìm kiếm mẫu...">
            </form>
        </header>

        <div class="password-form-card fadeInDown">
            <div class="text-center mb-4">
                <div class="stat-icon m-auto mb-3"
                    style="width: 70px; height: 70px; background: rgba(52, 152, 219, 0.1);">
                    <i class="fa fa-lock fa-2x"></i>
                </div>
                <h3 style="font-weight: 700;">Đổi mật khẩu</h3>
                <p class="text-muted" style="font-size: 14px;">Nhập thông tin bên dưới để cập nhật</p>
            </div>

            <form method="POST" action="../admin/pass.php">
                <input type="hidden" name="csrf_token" value="<?php echo Security\generate_csrf_token(); ?>">
                <div class="form-group mb-3">
                    <label>Mật khẩu cũ</label>
                    <input type="password" id="passwordold" class="form-control" name="passold"
                        placeholder="Nhập mật khẩu hiện tại" required>
                </div>

                <div class="form-group mb-3">
                    <label>Mật khẩu mới</label>
                    <input type="password" id="password1" class="form-control" name="passnew"
                        placeholder="Nhập mật khẩu mới" onkeyup="checkPasswords()" required>
                </div>

                <div class="form-group mb-2">
                    <label>Xác nhận mật khẩu</label>
                    <input type="password" id="password2" class="form-control" name="passnew2"
                        placeholder="Nhập lại mật khẩu mới" onkeyup="checkPasswords()" required>
                </div>

                <div id='message' class="mb-3" style="height: 20px; font-size: 13px; font-weight: 500;"></div>

                <button type="submit" id="btncp" class="btn btn-primary btn-lg">
                    <i class="fa fa-save mr-2"></i> CẬP NHẬT NGAY
                </button>
            </form>
        </div>
    </main>

    <script>
        // TRUYỀN CSRF TOKEN TỪ PHP SANG JAVASCRIPT
        window.CSRF_TOKEN = "<?php echo Security\generate_csrf_token(); ?>";
    </script>
    <script src="../home/js/my.js"></script>
    <style>
        .category-list li a {
            display: flex;
            align-items: center;
        }

        .btndelprotype:hover {
            color: #ff7675 !important;
        }
    </style>
</body>

</html>