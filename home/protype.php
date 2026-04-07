<?php
require_once "../includes/connectdb.php";
require_once "../includes/config_site.php";

// Set authentication constant for includes
if (!defined('MT_CNC_AUTH')) define('MT_CNC_AUTH', true);

$id = isset($_GET['id']) ? $_GET['id'] : "";
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; 
$offset = ($page - 1) * $limit;

// Fetch Products for this Category
$stmt = $link->prepare("SELECT id, proname, protype, prourl, description FROM products WHERE protype = ? ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $id, $limit, $offset);
$stmt->execute();
$result_fr1 = $stmt->get_result();

$show_product = "";
if ($result_fr1 && ($result_fr1->num_rows > 0)) {
    $atf_counter = 0;
    while ($row_fr1 = $result_fr1->fetch_assoc()) {
        $cleanName = Security\h($row_fr1["proname"]);
        $cleanType = Security\h($row_fr1["protype"]);
        $cleanDesc = Security\h($row_fr1["description"]);
        
        $atf_indices = [0, 1, 2, 3, 4, 6, 7, 9, 10];
        $is_atf = in_array($atf_counter, $atf_indices);
        $loading_attr = ($is_atf) ? "" : "loading=\"lazy\"";
        $fetch_priority = ($atf_counter === 0) ? "fetchpriority=\"high\"" : "";
        $atf_counter++;

        $show_product .= '
        <div class="product-item">
            <a href="viewimg.php?id=' . (int)$row_fr1["id"] . '">
                <div class="img-container">
                    <img src="imgs/' . Security\h($row_fr1["prourl"]) . '" alt="' . $cleanName . '" ' . $loading_attr . ' ' . $fetch_priority . '>
                </div>
                <div class="product-info">
                    <h5>' . $cleanName . '</h5>
                    <p>' . $cleanDesc . '</p>
                </div>
            </a>
            <button class="add-to-cart-btn" title="Thêm vào giỏ" onclick="addToCart(event, \'' . (int)$row_fr1["id"] . '\', \'' . $cleanName . '\', \'' . Security\h($row_fr1["prourl"]) . '\', \'' . $cleanType . '\')">
                <i class="fa fa-cart-plus"></i>
            </button>
        </div>';
    }
}

// Sidebar Categories Logic
$show_protype = "";
$show_protypelist = "";
$sql_types = "SELECT protype FROM products GROUP BY protype";
$res_types = $link->query($sql_types);
if ($res_types && $res_types->num_rows > 0) {
    while ($row_t = $res_types->fetch_assoc()) {
        $safeType = Security\h($row_t["protype"]);
        $urlType = urlencode($row_t["protype"]);
        $show_protype .= '<a href="protype.php?id=' . $urlType . '"><p class="nav-link type-link type" value="' . $safeType . '">' . $safeType . '</p></a>';
        $show_protypelist .= '<li><a href="protype.php?id=' . $urlType . '">' . $safeType . '</a></li>';
    }
}

// Pagination Logic
$stmt_count = $link->prepare('SELECT count(id) as total from products where protype = ?');
$stmt_count->bind_param("s", $id);
$stmt_count->execute();
$row_count = $stmt_count->get_result()->fetch_assoc();
$total = $row_count['total'];
$pages = ceil($total / $limit);
$netxpage = $page < $pages ? $page + 1 : $page;
$previouspage = $page > 1 ? $page - 1 : $page;

$pageslist = '<a href="protype.php?page=' . $previouspage . '&id=' . urlencode($id) . '">&laquo;</a>';
for ($i = 1; $i <= $pages; $i++) {
    $activeClass = ($page == $i) ? 'class="active"' : '';
    $pageslist .= '<a href="protype.php?page=' . $i . '&id=' . urlencode($id) . '" ' . $activeClass . '>' . $i . '</a>';
}
$pageslist .= '<a href="protype.php?page=' . $netxpage . '&id=' . urlencode($id) . '">&raquo;</a>';

// SEO Meta Preparation for header_site.php
$displayId = Security\h($id);
$pageTitle = $displayId . " - " . SITE_NAME;
$pageDesc = "Danh mục " . $displayId . ". " . SITE_DESCRIPTION;

include "header_site.php";
?>
<div class="listcnc" style="margin-top: 0;">
    <div class="container">
        <div class="row">
            <div class="col-lg-9">
                <h4 class="mb-4">Danh mục: <?php echo Security\h($id); ?></h4>
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
