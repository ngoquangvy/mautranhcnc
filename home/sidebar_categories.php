<?php
if (!defined('MT_CNC_AUTH')) exit('Access Denied');
/**
 * Shared Sidebar for Public Pages
 * Managed centrally in home/sidebar_categories.php.
 */
?>
<div class="col-lg-3 sidebar-wrapper" id="sidebar">
    <h5 class="sidebar-heading">Phân loại mẫu</h5>
    <ul class="ultypelist">
        <?php echo $show_protypelist; ?>
    </ul>
</div>
