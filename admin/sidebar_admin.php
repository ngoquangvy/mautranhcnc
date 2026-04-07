<?php
/**
 * Centralized Admin Sidebar
 * Handles branding, navigation, and category listing.
 * Moved to admin/ for better modularity.
 */

// 1. Determine local pathing for links (default to admin root)
$admin_root = (isset($is_nested_admin) && $is_nested_admin) ? "../" : "";
$current_script = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// 2. Fetch Categories for Sidebar (Centralized Query)
$show_protype = "";
$sql_sidebar_types = "SELECT protype, COUNT(*) as count FROM products GROUP BY protype ORDER BY protype ASC";
$result_sidebar_types = $link->query($sql_sidebar_types);
$total_types = 0;
if ($result_sidebar_types) {
    $total_types = $result_sidebar_types->num_rows;
    while ($type_row = $result_sidebar_types->fetch_assoc()) {
        $is_active_cat = (isset($id) && $id == $type_row["protype"]);
        $active_cat_class = $is_active_cat ? 'active-category' : '';
        $show_protype .= '
        <li class="sidebar-category-item d-flex align-items-center justify-content-between ' . $active_cat_class . '">
            <a href="' . $admin_root . 'protype.php?id=' . urlencode($type_row["protype"]) . '" class="flex-grow-1 ' . ($is_active_cat ? 'active' : '') . '">
                <span>' . htmlspecialchars($type_row["protype"]) . ' (' . $type_row["count"] . ')</span>
            </a>
            <button type="button" class="btn btn-link btn-sm text-danger btndelprotype p-0 ml-2" value="' . htmlspecialchars($type_row["protype"]) . '" title="Xóa danh mục">
                <i class="fa fa-trash"></i>
            </button>
        </li>';
    }
}

// 3. Cache Status
if (!isset($cacheEnabled)) {
    // Relative to this file (admin/sidebar_admin.php), cache.php is in ../includes/
    require_once dirname(__DIR__) . "/includes/cache.php";
    $cacheEnabled = FileCache::isEnabled();
}
?>

<aside class="admin-sidebar" id="sidebar">
    <div class="sidebar-content">
        <div class="sidebar-header">
            <img src="<?php echo SITE_LOGO_PREFIX . SITE_LOGO; ?>" alt="Logo" style="height: 40px; margin-bottom: 10px;">
            <h2><?php echo mb_strtoupper(SITE_NAME, 'UTF-8'); ?></h2>
            <p style="font-size: 12px; color: #95a5a5; margin: 0;">Admin Portal</p>
        </div>
        
        <div class="sidebar-nav">
            <div class="cache-switch-container">
                <div class="switch-label">
                    <span>Trạng thái Cache</span>
                    <i class="fa fa-bolt" style="color: <?= $cacheEnabled ? 'var(--accent-emerald)' : '#64748b' ?>"></i>
                </div>
                <form method="post" action="<?php echo $admin_root; ?>toggle_cache.php" id="cacheForm">
                    <input type="hidden" name="csrf_token" value="<?php echo Security\generate_csrf_token(); ?>">
                    <label class="toggle-switch">
                        <input type="checkbox" name="cache_toggle" onchange="document.getElementById('cacheForm').submit()" <?= $cacheEnabled ? 'checked' : '' ?>>
                        <span class="slider"><span class="slider-text"></span></span>
                    </label>
                </form>
            </div>

            <div class="nav-group-title">Menu Chính</div>
            <ul>
                <li>
                    <a href="<?php echo $admin_root; ?>admin.php" class="<?php echo ($current_script == 'admin.php' ? 'active' : ''); ?>">
                        <i class="fa fa-home mr-2"></i> <span>Tổng quan</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_root; ?>orders.php" class="<?php echo ($current_script == 'orders.php' ? 'active' : ''); ?>">
                        <i class="fa fa-shopping-cart mr-2"></i> <span>Quản lý Đơn hàng</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_root; ?>addproduct" class="<?php echo ($current_dir == 'addproduct' ? 'active' : ''); ?>">
                        <i class="fa fa-plus-circle mr-2"></i> <span>Thêm sản phẩm</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_root; ?>changepass.php" class="<?php echo ($current_script == 'changepass.php' ? 'active' : ''); ?>">
                        <i class="fa fa-key mr-2"></i> <span>Đổi mật khẩu</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $admin_root; ?>logout.php">
                        <i class="fa fa-sign-out mr-2"></i> <span>Đăng xuất</span>
                    </a>
                </li>
            </ul>

            <div class="nav-group-title mt-4">Danh mục sản phẩm</div>
            <ul class="category-list">
                <?php echo $show_protype; ?>
            </ul>
        </div>
    </div>
</aside>
