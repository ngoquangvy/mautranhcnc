<?php
if (!defined('MT_CNC_AUTH'))
    exit('Access Denied');
/**
 * Shared Footer for Public Pages
 * Managed centrally in home/footer_site.php.
 */
?>
<!-- Floating Contact -->
<div class="phone">
    <a href="<?php echo URL_ZALO; ?>" title="Zalo Contact">
        <img src="imgs/logo/zalo.png" alt="Zalo">
    </a>
    <a href="<?php echo URL_FACEBOOK; ?>" title="Facebook Contact">
        <img src="imgs/logo/fb.png" alt="Facebook">
    </a>
</div>

<!-- Footer Component -->
<footer class="bg-dark text-center text-lg-start text-white">
    <div class="container p-4">
        <div class="row">
            <div class="col-lg-6 col-md-12 mb-4 mb-md-0">
                <img class="logo-footer mb-4" src="<?php echo SITE_LOGO; ?>" alt="<?php echo SITE_NAME; ?> Logo"
                    style="max-width: 150px;">
                <h5 class="text-uppercase"><?php echo SITE_NAME; ?></h5>
                <p>Quản Lý: <?php echo CONTACT_MANAGER; ?> </p>
                <p>Phone: <?php echo CONTACT_PHONE; ?> </p>
                <p>FaceBook: <?php echo str_replace(['https://', 'http://', 'www.'], '', URL_FACEBOOK); ?> </p>
                <p>Zalo: <?php echo CONTACT_ZALO; ?> </p>
                <p>Gmail: <?php echo CONTACT_GMAIL; ?> </p>
            </div>

            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h5 class="text-uppercase mb-4">Danh mục phổ biến</h5>
                <ul class="list-unstyled mb-0 footer-categories" style="font-size: 0.9rem; line-height: 2;">
                    <?php
                    $sql_footer_labels = "SELECT protype FROM products GROUP BY protype ORDER BY COUNT(*) DESC LIMIT 6";
                    $res_footer_labels = $link->query($sql_footer_labels);
                    if ($res_footer_labels && $res_footer_labels->num_rows > 0) {
                        while ($row_label = $res_footer_labels->fetch_assoc()) {
                            $fSafeType = Security\h($row_label["protype"]);
                            $fUrlType = urlencode($row_label["protype"]);
                            echo '<li><a href="protype.php?id=' . $fUrlType . '" class="text-white-50"><i class="fa fa-angle-right mr-2"></i> ' . $fSafeType . '</a></li>';
                        }
                    }
                    ?>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h5 class="text-uppercase mb-4">Hỗ trợ khách hàng</h5>
                <p id="tktyc" class="text-white-50"><i class="fa fa-pencil-square-o mr-2"></i> Thiết kế mẫu theo ý - Rèn
                    file theo tâm - Đẹp không tì vết</p>
                <p class="text-white-50"><i class="fa fa-paper-plane mr-2"></i>Giao hàng toàn Thái Dương Hệ - File đến
                    sau một nén nhang</p>
                <p class="text-white-50"><i class="fa fa-shield mr-2"></i> Bảo hành đến khi nào ngỏm thì thôi</p>
            </div>
        </div>
    </div>

    <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2);">
        <a class="text-white-50" href="index.php"><?php echo SITE_NAME; ?></a>
    </div>
</footer>

<!-- Modal Menu Mobile -->
<div class="modal fade modal-menu" id="menuModal" tabindex="-1" role="dialog" style="z-index: 4000;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0" style="padding: 15px 20px 0;">
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"
                    onclick="$(this).blur(); $('#menuModal').modal('hide')"
                    style="font-size: 2.5rem; outline: none; padding: 10px; opacity: 1; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <ul class="navbar-nav w-100">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo URL_FACEBOOK; ?>">FaceBook</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo URL_ZALO; ?>">Zalo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="cart.php">Giỏ hàng</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Global Scripts -->
<script src="js/jquery.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/my.js?v=<?php echo time(); ?>"></script>