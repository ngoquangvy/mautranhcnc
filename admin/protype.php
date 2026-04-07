<?php
require_once "../includes/connectdb.php";
require_once "../includes/config_site.php";
if (!defined('SITE_LOGO_PREFIX')) define('SITE_LOGO_PREFIX', '../home/');

if (!isset($_SESSION["id"])) {
    header("location: ../admin");
    exit;
}

$id = isset($_GET['id']) ? $_GET['id'] : "";
if ($id == "") {
    header("location: admin.php");
    exit;
}

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 24;
$offset = ($page - 1) * $limit;

$show_product = "";

// Query products by category
$sql_fr1 = "SELECT * FROM products WHERE protype = ? ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $link->prepare($sql_fr1);
$stmt->bind_param("sii", $id, $limit, $offset);
$stmt->execute();
$result_fr1 = $stmt->get_result();

// Total count for pagination
$stmt_count = $link->prepare("SELECT COUNT(*) AS total FROM products WHERE protype = ?");
$stmt_count->bind_param("s", $id);
$stmt_count->execute();
$total_res = $stmt_count->get_result()->fetch_assoc();
$total_records = $total_res['total'];

if ($result_fr1 && ($result_fr1->num_rows > 0)) {
    while ($row_fr1 = mysqli_fetch_assoc($result_fr1)) {
        $show_product .= '
        <div class="product-card" id="' . $row_fr1["prourl"] . '">
            <div class="product-img-wrapper">
                <img src="../home/imgs/' . $row_fr1["prourl"] . '" alt="' . htmlspecialchars($row_fr1["proname"]) . '">
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
    $show_product = '<div class="col-12 text-center py-5"><h3>Không tìm thấy sản phẩm nào trong danh mục này.</h3></div>';
}

// Pagination Generation
$pages = ($total_records > 0) ? ceil($total_records / $limit) : 1;
$nextpage = $page < $pages ? $page + 1 : $pages;
$previouspage = $page > 1 ? $page - 1 : 1;

$pageslist = '<a href="?id=' . urlencode($id) . '&page=' . $previouspage . '">&laquo;</a>';
for ($i = 1; $i <= $pages; $i++) {
    $active_class = ($page == $i) ? 'class="active"' : '';
    $pageslist .= '<a href="?id=' . urlencode($id) . '&page=' . $i . '" ' . $active_class . '>' . $i . '</a>';
}
$pageslist .= '<a href="?id=' . urlencode($id) . '&page=' . $nextpage . '">&raquo;</a>';

// Sidebar logic moved to include
require_once "../includes/config_site.php";
if (!defined('SITE_LOGO_PREFIX')) define('SITE_LOGO_PREFIX', '../home/');

// Re-added for stats compatibility
$sql_types_count = "SELECT COUNT(DISTINCT protype) as total FROM products";
$total_types = $link->query($sql_types_count)->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh mục: <?php echo htmlspecialchars($id); ?> | <?php echo SITE_NAME; ?> Admin</title>
    <link rel="shortcut icon" href="<?php echo SITE_LOGO_PREFIX . SITE_LOGO; ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="../home/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/admin.css?v=1.6" rel="stylesheet">
    <script src="../home/js/jquery.js"></script>
    <script src="../home/js/bootstrap.min.js"></script>
</head>

<body>

    <?php include "sidebar_admin.php"; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <div>
                <h1 style="font-weight: 700; font-size: 28px; margin: 0;">Danh mục: <?php echo htmlspecialchars($id); ?>
                </h1>
                <p class="text-muted">Đang xem tất cả sản phẩm thuộc loại này</p>
            </div>

            <button class="btn btn-outline-danger btn-sm btndelprotype mx-4"
                value="<?php echo htmlspecialchars($id); ?>" style="height: fit-content; align-self: center;">
                <i class="fa fa-trash mr-2"></i> Xóa danh mục
            </button>

            <form action="admin.php" method="GET" class="search-wrapper">
                <i class="fa fa-search"></i>
                <input type="text" name="search" placeholder="Tìm tên mẫu hoặc loại...">
            </form>
        </header>

        <!-- Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-folder-open"></i></div>
                <div>
                    <div class="stat-count"><?php echo $total_records; ?></div>
                    <div class="stat-title">Mẫu trong mục này</div>
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
        
        // Xử lý XÓA DANH MỤC
        $(document).on('click', '.btndelprotype', function(e) {
            e.preventDefault();
            var categoryName = $(this).val();
            if (confirm('Bạn có chắc chắn muốn XÓA TOÀN BỘ danh mục "' + categoryName + '" không?')) {
                $.ajax({
                    url: 'deletept.php',
                    type: 'POST',
                    data: {
                        nameprotype: categoryName,
                        csrf_token: window.CSRF_TOKEN
                    },
                    success: function(response) {
                        location.href = 'admin.php'; // Quay về trang chủ sau khi xóa danh mục đang xem
                    }
                });
            }
        });
    </script>
    <script src="../home/js/my.js"></script>
    <script>
        // TRUYỀN CSRF TOKEN TỪ PHP SANG JAVASCRIPT
        window.CSRF_TOKEN = "<?php echo Security\generate_csrf_token(); ?>";
    </script>
    <script src="../home/js/my.js"></script>
</body>

</html>