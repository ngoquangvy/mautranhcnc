<?php
require_once "../includes/connectdb.php";
require_once "../includes/config_site.php";
require_once "../includes/cache.php";
if (!defined('SITE_LOGO_PREFIX')) define('SITE_LOGO_PREFIX', '../home/');

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

    $order_id = (int)$_GET['id'];
    $u_name = Security\u($_GET['name'] ?? 'Khách');
    $token = $_GET['token'] ?? '';

    // Xác thực Token CSRF & Phân quyền
    if (!Security\verify_csrf_token($token)) {
        header("location: orders.php?msg=auth_disabled");
        exit;
    }

    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    if ($action === 'trash') {
        $stmt = $link->prepare("UPDATE orders SET status = 'trashed', trashed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        if ($is_ajax) { echo json_encode(['ok' => true]); exit; }
        header("location: orders.php?msg=trashed&id=$order_id&name=$u_name");
        exit;
    } elseif ($action === 'process') {
        $stmt = $link->prepare("UPDATE orders SET status = 'processed', processed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        if ($is_ajax) { echo json_encode(['ok' => true]); exit; }
        header("location: orders.php?msg=processed&id=$order_id&name=$u_name");
        exit;
    } elseif ($action === 'restore') {
        $stmt = $link->prepare("UPDATE orders SET status = 'new', trashed_at = NULL, processed_at = NULL WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        if ($is_ajax) { echo json_encode(['ok' => true]); exit; }
        header("location: orders.php?view=trash&msg=restored&id=$order_id&name=$u_name");
        exit;
    } elseif ($action === 'delete') {
        if (!ADMIN_CAN_DELETE_ORDER) {
            if ($is_ajax) { echo json_encode(['ok' => false, 'error' => 'Permission denied']); exit; }
            header("location: orders.php?view=trash&msg=auth_disabled");
            exit;
        }
        $stmt1 = $link->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt1->bind_param("i", $order_id);
        $stmt1->execute();
        $stmt2 = $link->prepare("DELETE FROM orders WHERE id = ?");
        $stmt2->bind_param("i", $order_id);
        $stmt2->execute();
        if ($is_ajax) { echo json_encode(['ok' => true]); exit; }
        header("location: orders.php?view=trash&msg=deleted&id=$order_id&name=$u_name");
        exit;
    }
}

// Chế độ hiển thị: active (chờ xử lý + đã chốt) hoặc trash (thùng rác)
$view_mode = isset($_GET['view']) && $_GET['view'] === 'trash' ? 'trash' : 'active';


// Danh mục Sidebar
// Includes moved to sidebar or handled globally
require_once "../includes/config_site.php";
require_once "../includes/cache.php";
if (!defined('SITE_LOGO_PREFIX')) define('SITE_LOGO_PREFIX', '../home/');
$cacheEnabled = FileCache::isEnabled();

// Re-added for stats compatibility
$sql_types_count = "SELECT COUNT(DISTINCT protype) as total FROM products";
$total_types = $link->query($sql_types_count)->fetch_assoc()['total'];

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

// [MỚI] TÍNH TOÁN SỐ LƯỢNG ĐƠN HÀNG ĐỂ HIỆN TRÊN STATS BAR
$c_new = $link->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetch_row()[0] ?? 0;
$c_done = $link->query("SELECT COUNT(*) FROM orders WHERE status = 'processed'")->fetch_row()[0] ?? 0;
$c_trash = $link->query("SELECT COUNT(*) FROM orders WHERE status = 'trashed'")->fetch_row()[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng | Admin <?php echo SITE_NAME; ?></title>
    <link rel="shortcut icon" href="<?php echo SITE_LOGO_PREFIX . SITE_LOGO; ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="../home/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/admin.css?v=1.6" rel="stylesheet">
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

        /* [MỚI] BỔ SUNG CSS CHO NHÓM NÚT MOBILE */
        .mobile-actions-wrapper {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .mobile-actions-wrapper .btn-action {
            flex: 1;
            margin-bottom: 0;
            padding: 10px 5px;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        /* [MỚI] CSS TỐI ƯU HIỂN THỊ CHỐNG TRÀN (TRUNCATION) */

        /* [MỚI] CSS TỐI ƯU HIỂN THỊ CHỐNG TRÀN (TRUNCATION) */
        .customer-name {
            max-width: 200px; /* Giới hạn khoảng 25-30 ký tự */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 700;
            color: #1e293b;
            font-size: 1.1rem;
        }

        .customer-phone {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #0284c7;
            font-weight: 600;
            cursor: pointer;
        }

        .order-note-container {
            max-width: 350px;
            position: relative;
        }

        .order-note-content {
            max-height: 48px; /* Hiện khoảng 2 dòng */
            overflow: hidden;
            font-size: 0.9rem;
            color: #475569;
            line-height: 1.5;
            cursor: pointer;
            transition: max-height 0.3s ease;
            position: relative;
            word-break: break-all; /* Ép xuống hàng khi chuỗi quá dài không dấu cách */
            overflow-wrap: break-word;
        }

        .order-note-content.expanded {
            max-height: 2000px; /* Mở rộng tối đa */
            overflow: visible;
        }

        /* Hiệu ứng mờ ở cuối nếu nội dung bị cắt */
        .order-note-content:not(.expanded)::after {
            content: "";
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100%;
            height: 18px;
            background: linear-gradient(transparent, #f8fafc);
        }

        /* [MỚI] CSS CHO THANH THỐNG KÊ (STATS BAR) */

        /* [MỚI] CSS CHO THANH THỐNG KÊ (STATS BAR) */
        .stats-bar {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .stat-badge i { font-size: 1rem; }
        .stat-new { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .stat-processed { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .stat-trash { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

        @media (max-width: 992px) {
            .tab-stats-wrapper { flex-direction: column; align-items: flex-start !important; gap: 15px; }
            .stats-bar { width: 100%; justify-content: space-between; }
        }

        /* Order-specific Mobile Table Scaling */
        @media (max-width: 768px) {
            .admin-header h1 { font-size: 24px !important; }
            .table-custom, .table-custom tbody, .table-custom tr, .table-custom td {
                display: block; width: 100%;
            }
            .table-custom thead { display: none; }
            .tab-stats-wrapper {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
                background: #f1f5f9;
                padding: 15px;
                border-radius: 12px;
            }
            .stats-bar { width: 100%; flex-wrap: wrap; gap: 8px; }
            .stat-badge { padding: 4px 10px; font-size: 0.75rem; }
            .table-custom tbody tr {
                margin-bottom: 20px;
                padding: 15px;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            }
            .table-custom td { padding: 8px 0; border: none !important; text-align: left; }
            .table-custom td:last-child {
                margin-top: 10px;
                border-top: 1px dashed #cbd5e1 !important;
                padding-top: 15px;
            }
            .mobile-id-badge { float: left; margin-bottom: 5px; width: auto !important; padding: 0 !important; }
            .table-custom td.text-center {
                text-align: left !important;
                display: flex;
                justify-content: space-between;
                gap: 4%;
            }
            .btn-action { display: block; width: 48%; margin: 0; padding: 6px; font-size: 0.85rem; }
            .row-new { border-left: none !important; border-top: 5px solid #facc15 !important; }
            .row-warning { border-left: none !important; border-top: 5px solid #ef4444 !important; }
        }

    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include "sidebar_admin.php"; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header" style="display: block;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 style="font-weight: 700; font-size: 28px; margin: 0;">Quản lý Đơn Đặt Hàng</h1>
                    <p class="text-muted">Xem và quản lý các yêu cầu chốt file từ khách hàng</p>
                </div>
                <img src="<?php echo SITE_LOGO_PREFIX . SITE_LOGO; ?>" width="40" alt="Admin Badge" style="border-radius: 50%;">
            </div>

            <div class="header-tabs">
                <!-- Tabs nay da duoc di vao orders-container de Sync Ajax -->
            </div>
        </header>

        <div class="orders-container mt-4">
            <!-- [MỚI] THANH ĐIỀU HƯỚNG & THỐNG KÊ (Đã di chuyển vào đây để cập nhật Ajax) -->
            <div class="tab-stats-wrapper d-flex justify-content-between align-items-center mb-4">
                <div class="header-tabs" style="margin: 0;">
                    <a href="orders.php?view=active" class="<?php echo $view_mode == 'active' ? 'active' : ''; ?>">
                        <i class="fa fa-inbox mr-1"></i> Trạng thái Đơn hàng
                    </a>
                    <a href="orders.php?view=trash" class="<?php echo $view_mode == 'trash' ? 'active' : ''; ?>">
                        <i class="fa fa-trash mr-1"></i> Thùng rác sinh thái
                    </a>
                </div>

                <div class="stats-bar">
                    <div class="stat-badge stat-new" title="Đơn hàng chưa xử lý">
                        <i class="fa fa-clock-o"></i> Chưa chốt: <span><?php echo $c_new; ?></span>
                    </div>
                    <div class="stat-badge stat-processed" title="Đơn hàng đã hoàn tất">
                        <i class="fa fa-check-circle"></i> Đã chốt: <span><?php echo $c_done; ?></span>
                    </div>
                    <div class="stat-badge stat-trash" title="Đơn hàng trong thùng rác">
                        <i class="fa fa-trash"></i> Thùng rác: <span><?php echo $c_trash; ?></span>
                    </div>
                </div>
            </div>

            <?php
            if (isset($_GET['msg'])):
                $nID = isset($_GET['id']) ? '#' . htmlspecialchars($_GET['id']) : '';
                $nName = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : '';
                $txtInfo = trim("$nID $nName");
                ?>
                <?php if ($_GET['msg'] == 'trashed'): ?>
                    <div class="alert alert-warning mt-2 mb-4 auto-hide"><i class="fa fa-trash mr-2"></i> Đã đưa đơn
                        <strong><?php echo $txtInfo; ?></strong> vào Thùng rác!
                    </div>
                <?php elseif ($_GET['msg'] == 'processed'): ?>
                    <div class="alert alert-success mt-2 mb-4 auto-hide"><i class="fa fa-check mr-2"></i> Đã đánh dấu Chốt Đơn
                        <strong><?php echo $txtInfo; ?></strong> thành công!
                    </div>
                <?php elseif ($_GET['msg'] == 'deleted'): ?>
                    <div class="alert alert-danger mt-2 mb-4 auto-hide"><i class="fa fa-ban mr-2"></i> Đã xóa vĩnh viễn đơn
                        <strong><?php echo $txtInfo; ?></strong> khỏi máy chủ!
                    </div>
                <?php elseif ($_GET['msg'] == 'restored'): ?>
                    <div class="alert alert-info mt-2 mb-4 auto-hide"><i class="fa fa-refresh mr-2"></i> Khôi phục đơn
                        <strong><?php echo $txtInfo; ?></strong> thành công!
                    </div>
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
                                    <div class="customer-name" title="<?php echo htmlspecialchars($o['customer_name']); ?>">
                                        <?php echo htmlspecialchars($o['customer_name']); ?>
                                    </div>
                                    <div class="customer-phone mt-1" title="Click để mở Zalo / Gọi điện"
                                         onclick="window.open('https://zalo.me/<?php echo preg_replace('/[^0-9]/', '', $o['customer_phone']); ?>')">
                                        <i class="fa fa-phone mr-1"></i><?php echo htmlspecialchars($o['customer_phone']); ?>
                                    </div>
                                    <div class="order-date mt-1"><i class="fa fa-star-o mr-1"></i>Tạo:
                                        <?php echo date("d-m-Y H:i", strtotime($o['created_at'])); ?>
                                    </div>
                                    <?php if (!empty($o['processed_at'])): ?>
                                        <div class="order-date mt-1" style="color: #10b981; font-weight: 600;"><i
                                                class="fa fa-check-circle mr-1"></i>Chốt:
                                            <?php echo date("d-m-Y H:i", strtotime($o['processed_at'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($o['trashed_at'])): ?>
                                        <div class="order-date mt-1" style="color: #ef4444; font-weight: 600;"><i
                                                class="fa fa-clock-o mr-1"></i>Xóa:
                                            <?php echo date("d-m-Y H:i", strtotime($o['trashed_at'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="mb-1 font-weight-bold text-success"><i class="fa fa-files-o mr-2"></i>
                                        <?php echo Security\h($o['item_count']); ?> mẫu CNC</div>
                                    <div class="order-items">
                                        <?php echo nl2br(Security\h($o['items'])); ?>
                                    </div>
                                </td>
                                <td class="order-note-cell">
                                    <?php if (!empty($o['note'])): ?>
                                        <div class="order-note-container">
                                            <div class="order-note-content js-expand-note" title="Nhấn để xem đầy đủ/thu gọn">
                                                <?php echo nl2br(htmlspecialchars($o['note'])); ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <i class="text-muted">Không có ghi chú</i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="mobile-actions-wrapper">
                                        <?php
                                        $csrf_token = Security\generate_csrf_token();
                                        if ($view_mode == 'active'):
                                            ?>
                                            <?php if ($o['status'] == 'new'): ?>
                                                <a href="?action=process&id=<?php echo $o['id']; ?>&token=<?php echo $csrf_token; ?>&name=<?php echo Security\u($o['customer_name']); ?>"
                                                    class="btn-action btn-process">
                                                    <i class="fa fa-check"></i> Chốt đơn
                                                </a>
                                            <?php endif; ?>
                                            <a href="?action=trash&id=<?php echo $o['id']; ?>&token=<?php echo $csrf_token; ?>&name=<?php echo Security\u($o['customer_name']); ?>"
                                                class="btn-action btn-delete">
                                                <i class="fa fa-trash"></i> Bỏ đi
                                            </a>
                                        <?php else: // Trash view ?>
                                            <a href="?action=restore&id=<?php echo $o['id']; ?>&token=<?php echo $csrf_token; ?>&name=<?php echo Security\u($o['customer_name']); ?>"
                                                class="btn-action btn-restore">
                                                <i class="fa fa-refresh"></i> Khôi phục
                                            </a>
                                            <?php if (ADMIN_CAN_DELETE_ORDER): ?>
                                                <a href="?action=delete&id=<?php echo $o['id']; ?>&token=<?php echo $csrf_token; ?>&name=<?php echo Security\u($o['customer_name']); ?>"
                                                    class="btn-action btn-delete">
                                                    <i class="fa fa-trash"></i> Hủy Diệt
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
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
        $(document).on('click', '.btndelprotype', function (e) {
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
                    success: function (response) {
                        location.reload(); // Tải lại trang để cập nhật danh mục mới
                    }
                });
            }
        });

        // [MỚI] XỬ LÝ CLICK CÁC NÚT ACTION BẰNG AJAX (CHỐNG LẶP & MƯỢT MÀ)
        $(document).on('click', '.btn-action', function (e) {
            const url = $(this).attr('href');
            const $btn = $(this);
            const $row = $btn.closest('tr');

            // Đối với nút XÓA VĨNH VIỄN vẫn cần confirm
            if ($btn.hasClass('btn-delete') && url.includes('action=delete')) {
                if (!confirm('XÓA VĨNH VIỄN mất luôn dữ liệu đơn hàng! Bạn có chắc không?')) return false;
            }

            e.preventDefault();

            // Hi ứng Optimistic UI: Mờ dòng ngay lập tức
            $row.css({ 'opacity': '0.3', 'pointer-events': 'none' });

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (res) {
                    if (res.ok) {
                        // Phản hồi mượt mà: Hi ứng FadeOut rồi mất hẳn
                        $row.fadeOut(400, function () { $(this).remove(); });
                    } else {
                        alert('Lỗi: ' + (res.error || 'Thao tác thất bại'));
                        $row.css({ 'opacity': '1', 'pointer-events': 'auto' });
                    }
                },
                error: function () {
                    alert('Lỗi kết nối máy chủ!');
                    $row.css({ 'opacity': '1', 'pointer-events': 'auto' });
                }
            });
        });

        // Cập nhật Real-time: Tự động tải lại phần nội dung đơn hàng mỗi 5 giây
        setInterval(function () {
            // Chỉ tải lại nếu người dùng không đang thao tác
            if ($('.btn-action[style*="opacity: 0.3"]').length > 0) return;

            $.ajax({
                url: window.location.href,
                type: 'GET',
                cache: false,
                success: function (data) {
                    var newContent = $(data).find('.orders-container').html();
                    if (newContent) {
                        // Tránh nạp lại gây giật khi người dùng đang cuộn
                        $('.orders-container').html(newContent);
                    }
                }
            });
        }, 5000); // 5000 ms = 5 giây

        // [MỚI] TÍNH NĂNG MỞ RỘNG GHI CHÚ KHI CLICK (Dùng Event Delegation cho Ajax)
        $(document).on('click', '.js-expand-note', function() {
            $(this).toggleClass('expanded');
        });
    </script>
    <script>
        // TRUYỀN CSRF TOKEN TỪ PHP SANG JAVASCRIPT
        window.CSRF_TOKEN = "<?php echo Security\generate_csrf_token(); ?>";
    </script>
    <script src="../home/js/my.js"></script>
</body>

</html>