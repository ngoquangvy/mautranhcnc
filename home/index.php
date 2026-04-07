<?php
require_once "../includes/connectdb.php";
require_once "../includes/config_site.php";
require_once "../includes/cache.php";

// Set authentication constant for includes
if (!defined('MT_CNC_AUTH')) define('MT_CNC_AUTH', true);

if (isset($_GET['page'])) {
    $page = (int)$_GET['page'];
} else {
    $page = 1;
}

$cacheKey = "home_index_p" . $page;
$cachedData = FileCache::get($cacheKey);

if ($cachedData) {
    $show_product = $cachedData['show_product'];
    $pageslist = $cachedData['pageslist'];
    $show_protype = $cachedData['show_protype'];
    $show_protypelist = $cachedData['show_protypelist'];
} else {
    $limit = 24; 
    $offset = ($page - 1) * $limit;
    $show_product = "";
    
    // Fetch unique categories available
    $sql_fr1 = "SELECT protype from products GROUP BY protype order by id DESC LIMIT $limit OFFSET $offset";
    $result_fr1 = $link->query($sql_fr1);
    $atf_counter = 0;
    
    if ($result_fr1 && ($result_fr1->num_rows > 0)) {
        while ($row_fr1 = mysqli_fetch_assoc($result_fr1)) {
            $stmt_items = $link->prepare("SELECT * FROM products WHERE protype = ? ORDER BY id DESC");
            $stmt_items->bind_param("s", $row_fr1["protype"]);
            $stmt_items->execute();
            $result_fr = $stmt_items->get_result();
            
            if ($result_fr && ($result_fr->num_rows > 0)) {
                $row_fr = mysqli_fetch_assoc($result_fr);
                $cleanName = Security\h($row_fr["proname"] ?? '');
                $cleanType = Security\h($row_fr["protype"] ?? '');
                $cleanDesc = Security\h($row_fr["description"] ?? '');
                
                $atf_indices = [0, 1, 5, 6, 7, 11, 12, 13, 17, 18, 19];
                $is_atf = in_array($atf_counter, $atf_indices);
                $loading_attr = ($is_atf) ? "" : "loading=\"lazy\"";
                $fetch_priority = ($atf_counter === 0) ? "fetchpriority=\"high\"" : "";
                $atf_counter++;

                $show_product .= '
                  <div class="product-item">
                      <a href="protype.php?id=' . urlencode($row_fr["protype"] ?? '') . '">
                          <div class="img-container">
                              <img src="imgs/' . Security\h($row_fr["prourl"] ?? '') . '" alt="' . $cleanName . '" ' . $loading_attr . ' ' . $fetch_priority . '>
                          </div>
                          <div class="product-info">
                              <h5>' . $cleanName . '</h5>
                              <p>' . $cleanDesc . '</p>
                          </div>
                      </a>
                      <button class="add-to-cart-btn" title="Thêm vào giỏ" onclick="addToCart(event, \'' . (int)$row_fr["id"] . '\', \'' . $cleanName . '\', \'' . Security\h($row_fr["prourl"] ?? '') . '\', \'' . $cleanType . '\')">
                          <i class="fa fa-cart-plus"></i>
                      </button>
                  </div>';
            }
        }
    }

    // Pagination logic
    $stmt_p = $link->prepare("SELECT COUNT(DISTINCT protype) AS total FROM products");
    $stmt_p->execute();
    $total = $stmt_p->get_result()->fetch_assoc()['total'];
    $pages = ceil($total / $limit);
    $netxpage = $page < $pages ? $page + 1 : $page;
    $previouspage = $page > 1 ? $page - 1 : $page;
    
    $pageslist = '<a href="?page=' . $previouspage . '">&laquo;</a>';
    for ($i = 1; $i <= $pages; $i++) {
        $activeClass = ($page == $i) ? 'class="active"' : '';
        $pageslist .= '<a href="?page=' . $i . '" ' . $activeClass . '>' . $i . '</a>';
    }
    $pageslist .= '<a href="?page=' . $netxpage . '">&raquo;</a>';

    // Sidebar & Category List
    $show_protype = "";
    $show_protypelist = "";
    $res_t = $link->query("SELECT protype FROM products GROUP BY protype");
    if ($res_t && $res_t->num_rows > 0) {
        while ($row_t = $res_t->fetch_assoc()) {
            $safeT = Security\h($row_t["protype"] ?? '');
            $urlT = urlencode($row_t["protype"] ?? '');
            $show_protype .= '<a href="protype.php?id=' . $urlT . '"><p class="nav-link type-link type" value="' . $safeT . '">' . $safeT . '</p></a>';
            $show_protypelist .= '<li><a href="protype.php?id=' . $urlT . '">' . $safeT . '</a></li>';
        }
    }

    FileCache::set($cacheKey, [
        'show_product' => $show_product,
        'pageslist' => $pageslist,
        'show_protype' => $show_protype,
        'show_protypelist' => $show_protypelist
    ], FileCache::HOME_TTL);
}

include "header_site.php";
?>
<!-- Category Horizontal Scroll Bar -->
<div class="listcnc" style="margin-top: 0;">
    <div class="container">
        <div class="row">
            <div class="col-lg-9">
                <div id="products">
                    <?php echo $show_product ?>
                </div>
                <div class="paginationcenter">
                    <div class="pagination">
                        <?php echo $pageslist ?>
                    </div>
                </div>
            </div>
            <?php include "sidebar_categories.php"; ?>
        </div>
    </div>
</div>

<?php include "footer_site.php"; ?>
</body>
</html>
