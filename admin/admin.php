<?php
session_start();
require_once "../includes/connectdb.php";
require_once "../includes/cache.php";
$cacheEnabled = FileCache::isEnabled();

if (!isset($_SESSION["id"])) {
    header("location: ../admin");
    exit;
}

// Pagination & Search settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 24;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

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
                    <img src="../home/imgs/' . $row_fr1["prourl"] . '" alt="' . $row_fr1["proname"] . '">
                </div>
                <div class="product-actions" style="position: absolute; top: 10px; right: 10px; opacity: 1;">
                    <button class="btn btn-danger btn-sm btndel" value="' . $row_fr1["prourl"] . '" title="Xóa sản phẩm">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
                <div class="product-info" style="padding: 15px;">
                    <h3 style="font-size: 16px; margin-bottom: 5px;">' . htmlspecialchars($row_fr1["proname"]) . '</h3>
                    <span class="badge badge-secondary">' . htmlspecialchars($row_fr1["protype"]) . '</span>
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
    $search_query = $search != "" ? "&search=" . urlencode($search) : "";

    $pageslist = '<a href="?page=' . $previouspage . $search_query . '">&laquo;</a>';
    for ($i = 1; $i <= $pages; $i++) {
        $active_class = ($page == $i) ? 'class="active"' : '';
        $pageslist .= '<a href="?page=' . $i . $search_query . '" ' . $active_class . '>' . $i . '</a>';
    }
    $pageslist .= '<a href="?page=' . $nextpage . $search_query . '">&raquo;</a>';

    // Categories for Sidebar
    $show_protype = "";
    $sql_types = "SELECT protype, COUNT(*) as count FROM products GROUP BY protype ORDER BY protype ASC";
    $result_types = $link->query($sql_types);
    $total_types = 0;
    if ($result_types) {
        $total_types = $result_types->num_rows;
        while ($type_row = $result_types->fetch_assoc()) {
            $show_protype .= '
            <li class="sidebar-category-item">
                <a href="../admin/protype.php?id=' . urlencode($type_row["protype"]) . '">
                    <span>' . htmlspecialchars($type_row["protype"]) . ' (' . $type_row["count"] . ')</span>
                </a>
                <button type="button" class="btn btn-link btn-sm text-danger btndelprotype" value="' . htmlspecialchars($type_row["protype"]) . '" title="Xóa danh mục">
                    <i class="fa fa-trash"></i>
                </button>
            </li>';
        }
    }

    // Save to cache
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
    <title>Quản trị Dashboard | Mẫu CNC</title>
    <link rel="shortcut icon" href="../home/imgs/logo/mt_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="../home/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
    <script src="../home/js/jquery.js"></script>
    <script src="../home/js/bootstrap.min.js"></script>
</head>
<body>

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <img src="../home/imgs/logo/mt_logo.png" alt="Logo" style="height: 40px; margin-bottom: 10px;">
            <h2>MẪU CNC</h2>
            <p style="font-size: 12px; color: #95a5a6; margin: 0;">Admin Portal</p>
        </div>
        <div class="sidebar-nav">
            <div class="nav-group-title">
    <form method="post" action="toggle_cache.php" style="display:inline;margin-right:10px;">
        <button type="submit" class="btn btn-sm <?= $cacheEnabled ? 'btn-success' : 'btn-danger' ?>">
            Cache: <?= $cacheEnabled ? 'BẬT' : 'TẮT' ?>
        </button>
    </form>
    Menu Chính
</div>
            <ul>
                <li><a href="admin.php" class="<?php echo ($search==""?"active":""); ?>"><i class="fa fa-home mr-2"></i> <span>Tổng quan</span></a></li>
                <li><a href="addproduct"><i class="fa fa-plus-circle mr-2"></i> <span>Thêm sản phẩm</span></a></li>
                <li><a href="changepass.php"><i class="fa fa-key mr-2"></i> <span>Đổi mật khẩu</span></a></li>
                <li><a href="logout.php"><i class="fa fa-sign-out mr-2"></i> <span>Đăng xuất</span></a></li>
            </ul>

            <div class="nav-group-title mt-4">Danh mục sản phẩm</div>
            <ul class="category-list">
                <?php echo $show_protype; ?>
            </ul>
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
                <input type="text" name="search" placeholder="Tìm tên mẫu hoặc loại..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </header>

        <!-- Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-cubes fa-lg"></i></div>
                <div>
                    <div style="font-size: 24px; font-weight: 700;"><?php echo $total_records; ?></div>
                    <div style="font-size: 13px; color: #95a5a6;">Tổng sản phẩm</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-tags fa-lg"></i></div>
                <div>
                    <div style="font-size: 24px; font-weight: 700;"><?php echo $total_types; ?></div>
                    <div style="font-size: 13px; color: #95a5a6;">Danh mục</div>
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

    <script src="../home/js/my.js"></script>
    <style>
        /* Extra internal styling for sidebar delete button */
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