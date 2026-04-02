<?php
require_once "../includes/connectdb.php";
require_once "../includes/cache.php";
$cacheEnabled = FileCache::isEnabled();

if (!isset($_SESSION["id"])) {
    header("location: ../admin");
    exit;
}

// Chuyển hướng tự động sang Trang quản lý đơn hàng nếu mở bằng điện thoại
$is_mobile = preg_match("/(android|webos|iphone|ipad|ipod|blackberry|windows phone)/i", $_SERVER["HTTP_USER_AGENT"] ?? '');
if ($is_mobile) {
    header("Location: orders.php");
    exit;
}

// Pagination & Search settings
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 24;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// -------------------------------------------------------------
// [CODE MỚI - GIÁM SÁT VẬN HÀNH THÔNG BÁO]
// -------------------------------------------------------------
$notif_error_file = "logs/notif_error.log";
$show_notif_warning = false;
if (file_exists($notif_error_file) && filesize($notif_error_file) > 0) {
    $show_notif_warning = true;
}
// -------------------------------------------------------------

$cacheKey = "admin_index_p" . $page . "_s" . md5($search);
$cachedData = FileCache::get($cacheKey);

if ($cachedData) {
    $show_product = $cachedData['show_product'];
    $pageslist = $cachedData['pageslist'];
    $show_protype = $cachedData['show_protype'];
    $total_records = $cachedData['total_records'];
    $total_types = $cachedData['total_types'];
} else {
    $show_product = "";

    // Query products with Search support
    if ($search != "") {
        // GIẢI THÍCH BẢO MẬT:
        // -------------------------------------------------------------
        // Tại sao dùng Prepared Statement ở đây ($stmt)? 
        // Vì $search là dữ liệu biến (variable) từ người dùng ($_GET). 
        // Dùng dấu "?" giúp ngăn chặn việc kẻ xấu chèn mã SQL vào tham số tìm kiếm.

        $search_term = "%$search%";
        $sql_fr1 = "SELECT * FROM products WHERE proname LIKE ? OR protype LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $link->prepare($sql_fr1);
        $stmt->bind_param("ssii", $search_term, $search_term, $limit, $offset);
        $stmt->execute();
        $result_fr1 = $stmt->get_result();

        // Total count for search pagination
        $stmt_count = $link->prepare("SELECT COUNT(*) AS total FROM products WHERE proname LIKE ? OR protype LIKE ?");
        $stmt_count->bind_param("ss", $search_term, $search_term);
        $stmt_count->execute();
        $total_res = $stmt_count->get_result()->fetch_assoc();
        $total_records = $total_res['total'];
    } else {
        // TẠI SAO DÙNG query($sql) TRỰC TIẾP Ở ĐÂY LÀ AN TOÀN?
        // Vì câu lệnh này là hằng số (constant), không chứa bất kỳ input nào từ phía người dùng.

        $sql_fr1 = "SELECT * FROM products ORDER BY id DESC LIMIT $limit OFFSET $offset";
        $result_fr1 = $link->query($sql_fr1);

        $total_res = $link->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc();
        $total_records = $total_res['total'];
    }

    if ($result_fr1 && ($result_fr1->num_rows > 0)) {
        while ($row_fr1 = mysqli_fetch_assoc($result_fr1)) {
            $show_product .= '
            <div class="product-card" id="' . $row_fr1["prourl"] . '">
                <div class="product-img-wrapper">
                    <img src="../home/imgs/' . $row_fr1["prourl"] . '" alt="' . htmlspecialchars($row_fr1["proname"]) . '" loading="lazy">
                    <button class="btn-delete-card btndel" value="' . $row_fr1["prourl"] . '" title="Xóa mẫu">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
                <div class="product-info">
                    <h3 title="' . htmlspecialchars($row_fr1["proname"]) . '">' . htmlspecialchars($row_fr1["proname"]) . '</h3>
                    <span class="product-badge">' . htmlspecialchars($row_fr1["protype"]) . '</span>
                </div>
            </div>';
        }
    } else {
        $show_product = '<div class="col-12 text-center py-5"><h3>Không tìm thấy sản phẩm nào.</h3></div>';
    }

    // Pagination Generation
    $pages = ($total_records > 0) ? ceil($total_records / $limit) : 1;
    $nextpage = $page < $pages ? $page + 1 : $pages;
    $previouspage = $page > 1 ? $page - 1 : 1;

    $pageslist = '<a href="?page=' . $previouspage . '&search=' . urlencode($search) . '">&laquo;</a>';
    for ($i = 1; $i <= $pages; $i++) {
        $active_class = ($page == $i) ? 'class="active"' : '';
        $pageslist .= '<a href="?page=' . $i . '&search=' . urlencode($search) . '" ' . $active_class . '>' . $i . '</a>';
    }
    $pageslist .= '<a href="?page=' . $nextpage . '&search=' . urlencode($search) . '">&raquo;</a>';

    // Categories for Sidebar Standardized from orders.php
    $show_protype = "";
    $sql_types = "SELECT protype, COUNT(*) as count FROM products GROUP BY protype ORDER BY protype ASC";
    $result_types = $link->query($sql_types);
    if ($result_types) {
        $total_types = $result_types->num_rows;
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

    // Cache results (1 hour)
    FileCache::set($cacheKey, [
        'show_product' => $show_product,
        'pageslist' => $pageslist,
        'show_protype' => $show_protype,
        'total_records' => $total_records,
        'total_types' => $total_types
    ], FileCache::ADMIN_TTL);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý kho mẫu | Mẫu CNC Admin</title>
    <link rel="shortcut icon" href="../home/imgs/logo/mt_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="../home/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/admin.css?v=1.5" rel="stylesheet">
    <script src="../home/js/jquery.js"></script>
    <script src="../home/js/bootstrap.min.js"></script>
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
                    <li><a href="admin.php" class="<?php echo ($search == "" ? "active" : ""); ?>"><i class="fa fa-home mr-2"></i> <span>Tổng quan</span></a></li>
                    <li><a href="orders.php"><i class="fa fa-shopping-cart mr-2"></i> <span>Quản lý Đơn hàng</span></a></li>
                    <li><a href="addproduct"><i class="fa fa-plus-circle mr-2"></i> <span>Thêm sản phẩm</span></a></li>
                    <li><a href="changepass.php"><i class="fa fa-key mr-2"></i> <span>Đổi mật khẩu</span></a></li>
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
                <h1 style="font-weight: 700; font-size: 28px; margin: 0;">Quản lý kho mẫu</h1>
                <p class="text-muted">Chào mừng trở lại, Quản trị viên</p>
            </div>

            <form action="admin.php" method="GET" class="search-wrapper">
                <i class="fa fa-search"></i>
                <input type="text" name="search" placeholder="Tìm tên mẫu hoặc loại..."
                    value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </header>

        <!-- ─────────────────────────────────────────────────────────────
             CẢNH BÁO LỖI GỬI MAIL (CHỈ HIỂN THỊ KHI CÓ LỖI TRONG LOG)
             ───────────────────────────────────────────────────────────── -->
        <?php if ($show_notif_warning): ?>
            <div class="alert alert-danger mb-4 shadow-sm" style="border-radius: 12px; border-left: 5px solid #d63031; background: #fffcfc;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <i class="fa fa-exclamation-triangle mr-2 text-danger"></i> 
                        <strong class="text-danger">Cảnh báo vận hành:</strong> Có lỗi phát sinh khi gửi thông báo Email gần đây! 
                    </div>
                    <a href="logs/notif_error.log" target="_blank" class="btn btn-sm btn-outline-danger" style="border-radius: 8px;">
                        <i class="fa fa-search mr-1"></i> Kiểm tra lỗi
                    </a>
                </div>
            </div>
        <?php endif; ?>
        <!-- ───────────────────────────────────────────────────────────── -->

        <!-- Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-cubes"></i></div>
                <div>
                    <div class="stat-count"><?php echo $total_records; ?></div>
                    <div class="stat-title">Sản phẩm</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--accent-emerald), #059669);"><i
                        class="fa fa-tags"></i></div>
                <div>
                    <div class="stat-count"><?php echo $total_types; ?></div>
                    <div class="stat-title">Danh mục</div>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="products">
            <?php echo $show_product; ?>
        </div>

        <!-- Pagination -->
        <div class="pagination-wrapper">
            <div class="custom-pagination">
                <?php echo $pageslist; ?>
            </div>
        </div>
    </main>

    <script>
        // TRUYỀN CSRF TOKEN TỪ PHP SANG JAVASCRIPT
        window.CSRF_TOKEN = "<?php echo Security\generate_csrf_token(); ?>";
    </script>
    <script src="../home/js/my.js"></script>
    <style>
        .sidebar-category-item {
            transition: all 0.2s;
            padding-right: 15px;
        }
        .btndelprotype {
            opacity: 0.3;
            transition: opacity 0.2s;
        }
        .sidebar-category-item:hover .btndelprotype {
            opacity: 1;
        }
    </style>
</body>

</html>