<?php
require_once "../includes/connectdb.php";
require_once "../includes/cache.php";

$cacheEnabled = FileCache::isEnabled();

if (!isset($_SESSION["id"])) {
    header("location: ../admin");
    exit;
}

// Các hành động xử lý đơn hàng
if (isset($_GET['action']) && isset($_GET['id'])) {
    // 1. KIỂM TRA CSRF TOKEN (Bảo mật hành động nhạy cảm)
    // -------------------------------------------------------------
    // Tại sao bắt buộc phải có &token=... trên URL?
    // Để ngăn chặn tấn công CSRF. Nếu không có token, kẻ xấu có thể 
    // lừa Admin bấm vào một link như: orders.php?action=trash&id=100
    // từ một trang web khác để bí mật xóa đơn hàng của bạn.
    if (!isset($_GET['token']) || !Security\verify_csrf_token($_GET['token'])) {
        die("Lỗi bảo mật: CSRF Token không hợp lệ!");
    }
    // -------------------------------------------------------------

    $action = $_GET['action'];
    $order_id = (int) $_GET['id'];

    // Lấy tên khách hàng trước
    $c_name = "Khách";
    $query_name = $link->prepare("SELECT customer_name FROM orders WHERE id = ?");
    $query_name->bind_param("i", $order_id);
    $query_name->execute();
    $res_name = $query_name->get_result();
    if ($n_row = $res_name->fetch_assoc())
        $c_name = $n_row['customer_name'];
    $u_name = urlencode($c_name);

    if ($action === 'trash') {
        $stmt = $link->prepare("UPDATE orders SET status = 'trashed', trashed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        header("location: orders.php?msg=trashed&id=$order_id&name=$u_name");
        exit;
    } elseif ($action === 'process') {
        $stmt = $link->prepare("UPDATE orders SET status = 'processed', processed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        header("location: orders.php?msg=processed&id=$order_id&name=$u_name");
        exit;
    } elseif ($action === 'restore') {
        $stmt = $link->prepare("UPDATE orders SET status = 'new', trashed_at = NULL, processed_at = NULL WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        header("location: orders.php?view=trash&msg=restored&id=$order_id&name=$u_name");
        exit;
    } elseif ($action === 'delete') {
        // TẠM THỜI KHÓA API XÓA VĨNH VIỄN
        // Phục vụ cho mục đích xây dựng hệ thống Role-base Auth (Phân quyền nhân viên)
        // Khi nào có Nhân viên Đăng nhập thì chỉ Admin mới được chạy lệnh này, nhân viên thì không.

        /* [DO NOT UNCOMMENT UNTIL ROLE AUTH IS READY]
        $stmt1 = $link->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt1->bind_param("i", $order_id);
        $stmt1->execute();

        $stmt2 = $link->prepare("DELETE FROM orders WHERE id = ?");
        $stmt2->bind_param("i", $order_id);
        $stmt2->execute();
        */

        // Gọi thẳng màn hình báo lỗi cấp quyền
        header("location: orders.php?view=trash&msg=auth_disabled");
        exit;
    }
}

// Chế độ hiển thị: active (chờ xử lý + đã chốt) hoặc trash (thùng rác)
$view_mode = isset($_GET['view']) && $_GET['view'] === 'trash' ? 'trash' : 'active';


// Danh mục Sidebar
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

// Lấy danh sách 100 đơn hàng mới nhất tùy view_mode
$orders = [];
if ($view_mode === 'active') {
    $where_clause = "WHERE o.status IN ('new', 'processed')";
} else {
    $where_clause = "WHERE o.status = 'trashed'";
}

$sql_orders = "SELECT o.id, o.customer_name, o.customer_phone, o.note, o.created_at, o.processed_at, o.trashed_at, o.status,
               GROUP_CONCAT(CONCAT('- ', oi.product_name, ' (Mã: ', oi.product_id, ')') SEPARATOR '\n') as items,
               COUNT(oi.id) as item_count
               FROM orders o
               LEFT JOIN order_items oi ON o.id = oi.order_id
               $where_clause
               GROUP BY o.id
               ORDER BY o.status ASC, o.created_at DESC
               LIMIT 100";
$res_orders = $link->query($sql_orders);
if ($res_orders && $res_orders->num_rows > 0) {
    while ($row = $res_orders->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng | Admin Mẫu CNC</title>
    <link rel="shortcut icon" href="../home/imgs/logo/mt_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="../home/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/admin.css?v=1.5" rel="stylesheet">
    <script src="../home/js/jquery.js"></script>
    <script src="../home/js/bootstrap.min.js"></script>
    <style>
        .orders-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .table-custom {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .table-custom thead th {
            border: none;
            font-weight: 600;
            color: #64748b;
            padding: 10px 15px;
        }

        .table-custom tbody tr {
            background: #f8fafc;
            transition: all 0.2s;
        }

        .table-custom tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            background: white;
            border-radius: 8px;
        }

        .table-custom td {
            padding: 15px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .table-custom td:first-child {
            border-left: 1px solid #e2e8f0;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .table-custom td:last-child {
            border-right: 1px solid #e2e8f0;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .customer-name {
            font-weight: 700;
            color: #1e293b;
            font-size: 1.1rem;
        }

        .customer-phone {
            color: #0284c7;
            font-weight: 600;
            cursor: pointer;
        }

        .order-date {
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .order-items {
            font-size: 0.9rem;
            color: #334155;
            line-height: 1.6;
            background: #e2e8f0;
            padding: 8px;
            border-radius: 6px;
        }

        .btn-action {
            width: 100%;
            padding: 8px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            text-align: center;
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .btn-process {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            cursor: pointer;
        }

        .btn-process:hover {
            background: #166534;
            color: white;
            text-decoration: none;
        }

        .btn-delete {
            background: #fef2f2;
            color: #ef4444;
            border: 1px solid #fecaca;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: #ef4444;
            color: white;
            text-decoration: none;
        }

        .btn-restore {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            cursor: pointer;
        }

        .btn-restore:hover {
            background: #0369a1;
            color: white;
            text-decoration: none;
        }

        .row-new {
            background: #fffbe8 !important;
            border-left: 4px solid #facc15 !important;
        }

        .row-warning {
            background: #fef2f2 !important;
            border-left: 4px solid #ef4444 !important;
        }

        .row-processed {
            opacity: 0.7;
            background: #f8fafc !important;
            border-left: 4px solid #94a3b8 !important;
        }

        .header-tabs {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .header-tabs a {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            border: 1px solid #e2e8f0;
            color: #64748b;
            background: white;
        }

        .header-tabs a.active {
            background: #1e293b;
            color: white;
            border-color: #1e293b;
        }

        /* Responsive Mobile Layout For Orders Only */
        @media (max-width: 768px) {
            .admin-sidebar {
                display: none !important;
            }

            .admin-main {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 15px !important;
            }

            .admin-header h1 {
                font-size: 24px !important;
            }

            .table-custom,
            .table-custom tbody,
            .table-custom tr,
            .table-custom td {
                display: block;
                width: 100%;
            }

            .table-custom thead {
                display: none;
                /* Ẩn thẻ tiêu đề bảng trên Mobile */
            }

            .table-custom tbody tr {
                margin-bottom: 20px;
                padding: 15px;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }

            .table-custom td {
                padding: 8px 0;
                border: none !important;
                text-align: left;
            }

            .table-custom td:last-child {
                margin-top: 10px;
                border-top: 1px dashed #cbd5e1 !important;
                padding-top: 15px;
            }

            /* Gọn gàng ID và Badge - Ở góc trái */
            .mobile-id-badge {
                float: left;
                margin-bottom: 5px;
                border: none;
                width: auto !important;
                padding: 0 !important;
                font-size: 0.9rem;
            }

            /* Dàn nút ngang 50/50 */
            .table-custom td.text-center {
                text-align: left !important;
                display: flex;
                justify-content: space-between;
                gap: 4%;
            }

            .btn-action {
                display: block;
                width: 48%;
                margin: 0;
                padding: 6px;
                font-size: 0.85rem;
            }

            .btn-action.btn-delete {
                width: 48%;
                margin: 0;
            }

            .row-new {
                border-left: none !important;
                border-top: 5px solid #facc15 !important;
            }

            .row-warning {
                border-left: none !important;
                border-top: 5px solid #ef4444 !important;
            }
        }
    </style>
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
            <div class="cache-switch-container">
                <div class="switch-label">
                    <span>Trạng thái Cache</span>
                    <i class="fa fa-bolt" style="color: <?= $cacheEnabled ? 'var(--accent-emerald)' : '#64748b' ?>"></i>
                </div>
                <form method="post" action="toggle_cache.php" id="cacheForm">
                    <!-- 
                        GIẢI THÍCH BẢO MẬT: CSRF CHO TOGGLE CACHE
                        Ngay cả những hành động cấu hình hệ thống nhỏ cũng cần CSRF 
                        để tránh bị tin tặc lừa Admin bấm vào link lạ làm thay đổi 
                        tình trạng vận hành của Website.
                    -->
                    <input type="hidden" name="csrf_token" value="<?php echo Security\generate_csrf_token(); ?>">
                    <label class="toggle-switch">
                        <input type="checkbox" name="cache_toggle"
                            onchange="document.getElementById('cacheForm').submit()" <?= $cacheEnabled ? 'checked' : '' ?>>
                        <span class="slider"><span class="slider-text"></span></span>
                    </label>
                </form>
            </div>

            <div class="nav-group-title">Menu Chính</div>
            <ul>
                <li><a href="admin.php"><i class="fa fa-home mr-2"></i> <span>Tổng quan</span></a></li>
                <li><a href="orders.php" class="active"><i class="fa fa-shopping-cart mr-2"></i> <span>Quản lý Đơn
                            hàng</span></a></li>
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
        <header class="admin-header" style="display: block;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 style="font-weight: 700; font-size: 28px; margin: 0;">Quản lý Đơn Đặt Hàng</h1>
                    <p class="text-muted">Xem và quản lý các yêu cầu chốt file từ khách hàng</p>
                </div>
                <img src="../home/imgs/logo/mt_logo.png" width="40" alt="Admin Badge" style="border-radius: 50%;">
            </div>

            <div class="header-tabs">
                <a href="orders.php?view=active" class="<?php echo $view_mode == 'active' ? 'active' : ''; ?>">
                    <i class="fa fa-inbox mr-1"></i> Trạng thái Đơn hàng
                </a>
                <a href="orders.php?view=trash" class="<?php echo $view_mode == 'trash' ? 'active' : ''; ?>">
                    <i class="fa fa-trash mr-1"></i> Thùng rác sinh thái
                </a>
            </div>
        </header>

        <div class="orders-container mt-4">
            <?php
            if (isset($_GET['msg'])):
                $nID = isset($_GET['id']) ? '#' . htmlspecialchars($_GET['id']) : '';
                $nName = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : '';
                $txtInfo = trim("$nID $nName");
                ?>
                <?php if ($_GET['msg'] == 'trashed'): ?>
                    <div class="alert alert-warning mt-2 mb-4 auto-hide"><i class="fa fa-trash mr-2"></i> Đã đưa đơn
                        <strong><?php echo $txtInfo; ?></strong> vào Thùng rác!</div>
                <?php elseif ($_GET['msg'] == 'processed'): ?>
                    <div class="alert alert-success mt-2 mb-4 auto-hide"><i class="fa fa-check mr-2"></i> Đã đánh dấu Chốt Đơn
                        <strong><?php echo $txtInfo; ?></strong> thành công!</div>
                <?php elseif ($_GET['msg'] == 'deleted'): ?>
                    <div class="alert alert-danger mt-2 mb-4 auto-hide"><i class="fa fa-ban mr-2"></i> Đã xóa vĩnh viễn đơn
                        <strong><?php echo $txtInfo; ?></strong> khỏi máy chủ!</div>
                <?php elseif ($_GET['msg'] == 'restored'): ?>
                    <div class="alert alert-info mt-2 mb-4 auto-hide"><i class="fa fa-refresh mr-2"></i> Khôi phục đơn
                        <strong><?php echo $txtInfo; ?></strong> thành công!</div>
                <?php elseif ($_GET['msg'] == 'auth_disabled'): ?>
                    <div class="alert alert-secondary mt-2 mb-4 auto-hide"><i class="fa fa-lock mr-2"></i> Quyền Xóa Vĩnh Viễn
                        đang bị tạm khóa để chuẩn bị hệ thống Role-based Auth!</div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (count($orders) > 0): ?>
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th width="5%">#ID</th>
                            <th width="20%">Khách Hàng</th>
                            <th width="45%">Danh Sách Mẫu Trang</th>
                            <th width="20%">Ghi chú</th>
                            <th width="10%">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o):
                            // Logic Nhận diện màu sắc Row
                            $row_class = 'row-processed'; // Default for processed
                            if ($o['status'] == 'new') {
                                $is_late = (time() - strtotime($o['created_at'])) > 86400; // Quá 24 tiếng
                                $row_class = $is_late ? 'row-warning' : 'row-new';
                            }
                            ?>
                            <tr class="<?php echo $view_mode == 'active' ? $row_class : ''; ?>">
                                <td class="font-weight-bold text-muted mobile-id-badge">
                                    #<?php echo $o['id']; ?>
                                    <?php if ($o['status'] == 'new'): ?>
                                        <span class="badge badge-danger ml-2 blink"
                                            style="animation: blinker 1.5s linear infinite;">MỚI</span>
                                        <style>
                                            @keyframes blinker {
                                                50% {
                                                    opacity: 0.3;
                                                }
                                            }
                                        </style>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="customer-name"><?php echo htmlspecialchars($o['customer_name']); ?></div>
                                    <div class="customer-phone mt-1"><i
                                            class="fa fa-phone mr-1"></i><?php echo htmlspecialchars($o['customer_phone']); ?>
                                    </div>
                                    <div class="order-date mt-1"><i class="fa fa-star-o mr-1"></i>Tạo:
                                        <?php echo date("d-m-Y H:i", strtotime($o['created_at'])); ?></div>
                                    <?php if (!empty($o['processed_at'])): ?>
                                        <div class="order-date mt-1" style="color: #10b981; font-weight: 600;"><i
                                                class="fa fa-check-circle mr-1"></i>Chốt:
                                            <?php echo date("d-m-Y H:i", strtotime($o['processed_at'])); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($o['trashed_at'])): ?>
                                        <div class="order-date mt-1" style="color: #ef4444; font-weight: 600;"><i
                                                class="fa fa-clock-o mr-1"></i>Xóa:
                                            <?php echo date("d-m-Y H:i", strtotime($o['trashed_at'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="mb-1 font-weight-bold text-success"><i class="fa fa-files-o mr-2"></i>
                                        <?php echo Security\h($o['item_count']); ?> mẫu CNC</div>
                                    <div class="order-items">
                                        <?php echo nl2br(Security\h($o['items'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <em
                                        style="color: #64748b; font-size: 0.9rem;"><?php echo empty($o['note']) ? "(Không ghi chú)" : htmlspecialchars($o['note']); ?></em>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $csrf_token = Security\generate_csrf_token();
                                    if ($view_mode == 'active'):
                                        ?>
                                        <?php if ($o['status'] == 'new'): ?>
                                            <a href="?action=process&id=<?php echo $o['id']; ?>&token=<?php echo $csrf_token; ?>"
                                                class="btn-action btn-process">
                                                <i class="fa fa-check"></i> Đã chốt
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=trash&id=<?php echo $o['id']; ?>&token=<?php echo $csrf_token; ?>"
                                            class="btn-action btn-delete">
                                            <i class="fa fa-trash"></i> Bỏ đi
                                        </a>
                                    <?php else: // Trash view ?>
                                        <a href="?action=restore&id=<?php echo $o['id']; ?>&token=<?php echo $csrf_token; ?>"
                                            class="btn-action btn-restore" style="width: 100%;">
                                            <i class="fa fa-refresh"></i> Khôi phục lại
                                        </a>
                                        <!-- TẠM ẨN NÚT HỦY DIỆT ĐỂ CHỜ HỆ THỐNG ROLE-BASED AUTH TRONG TƯƠNG LAI
                                <a href="?action=delete&id=<?php echo $o['id']; ?>&token=<?php echo $csrf_token; ?>" class="btn-action btn-delete" onclick="return confirm('XÓA VĨNH VIỄN mất luôn dữ liệu đơn hàng! Bạn có chắc không?');">
                                    <i class="fa fa-ban"></i> Hủy Diệt
                                </a>
                                -->
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fa fa-inbox mb-3" style="font-size: 4rem; color: #cbd5e1;"></i>
                    <h3 class="text-muted">Chưa có đơn hàng nào</h3>
                    <p>Khách bấm gửi giỏ hàng thì dữ liệu sẽ hiện ở đây.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Tự động ẩn thông báo sau 3.5 giây
        setTimeout(function () {
            $('.auto-hide').fadeOut('slow');
        }, 3500);

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
                        csrf_token: '<?php echo Security\generate_csrf_token(); ?>'
                    },
                    success: function(response) {
                        location.reload(); // Tải lại trang để cập nhật danh mục mới
                    }
                });
            }
        });

        // Cập nhật Real-time: Tự động tải lại phần nội dung đơn hàng mỗi 5 giây
        setInterval(function () {
            $.ajax({
                url: window.location.href, 
                type: 'GET',
                cache: false, 
                success: function (data) {
                    var newContent = $(data).find('.orders-container').html();
                    if (newContent) {
                        $('.orders-container').html(newContent);
                    }
                }
            });
        }, 5000); // 5000 ms = 5 giây
    </script>
    <script>
        // TRUYỀN CSRF TOKEN TỪ PHP SANG JAVASCRIPT
        window.CSRF_TOKEN = "<?php echo Security\generate_csrf_token(); ?>";
    </script>
    <script src="../home/js/my.js"></script>
</body>

</html>