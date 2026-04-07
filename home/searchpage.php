<?php
require_once "../includes/connectdb.php";
require_once "../includes/config_site.php";

// Set authentication constant for includes
if (!defined('MT_CNC_AUTH')) define('MT_CNC_AUTH', true);

$searchtext = mb_substr(trim($_GET['search'] ?? ''), 0, 50); 
if (isset($_POST['search'])) {
    $searchtext = mb_substr(trim($_POST['search']), 0, 50);
}

// Pagination setup
$limit = 24; 
if (isset($_GET['page'])) {
    $page = (int)$_GET['page']; 
    $searchtext = mb_substr($_GET['search'] ?? '', 0, 50);
} else {
    $page = 1; 
}

// Search Throttling (Security)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$now = time();
$window = 60; 
$max_requests = 10; 

if (!isset($_SESSION['search_history'])) {
    $_SESSION['search_history'] = [];
}
$_SESSION['search_history'] = array_filter($_SESSION['search_history'], function($t) use ($now, $window) {
    return $t > ($now - $window);
});
if (count($_SESSION['search_history']) >= $max_requests) {
    die("Bạn đang tìm kiếm quá nhanh. Vui lòng thử lại sau 1 phút.");
}
$_SESSION['search_history'][] = $now;

$offset = ($page - 1) * $limit;

// Vietnamese Regex Helper
function vi_to_regex($str) {
    if (empty($str)) return ".*";
    $str = preg_quote($str, '/');
    $map = [
        'a' => '[aàáảãạăằắẳẵặâầấẩẫậ]', 'e' => '[eèéẻẽẹêềếểễệ]', 'i' => '[iìíỉĩị]',
        'o' => '[oòóỏõọôồốổỗộơờớởỡợ]', 'u' => '[uùúủũụưừứửữự]', 'y' => '[yỳýỷỹỵ]', 'd' => '[dđ]',
        'A' => '[AÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬ]', 'E' => '[EÈÉẺẼẸÊỀẾỂỄỆ]', 'I' => '[IÌÍỈĨỊ]',
        'O' => '[OÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢ]', 'U' => '[UÙÚỦŨỤƯỪỨỬỮỰ]', 'Y' => '[YỲÝỶỸỴ]', 'D' => '[DĐ]'
    ];
    $result = "";
    for ($i = 0; $i < mb_strlen($str, 'UTF-8'); $i++) {
        $char = mb_substr($str, $i, 1, 'UTF-8');
        if (isset($map[$char])) {
            $result .= $map[$char];
        } else {
            $result .= $char;
        }
    }
    return $result;
}

$regex_pattern = vi_to_regex($searchtext);

// Primary Search Query
$stmt = $link->prepare("SELECT * FROM products WHERE proname REGEXP ? OR protype REGEXP ? ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ssii", $regex_pattern, $regex_pattern, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$show_product = "";
if ($result->num_rows > 0) {
    $atf_counter = 0;
    while ($row = $result->fetch_assoc()) {
        $atf_indices = [0, 1, 5, 6, 7, 11, 12, 13, 17, 18, 19];
        $is_atf = in_array($atf_counter, $atf_indices);
        $loading_attr = ($is_atf) ? "" : "loading=\"lazy\"";
        $fetch_priority = ($atf_counter === 0) ? "fetchpriority=\"high\"" : "";
        $atf_counter++;
        
        $cleanName = Security\h($row["proname"]);
        $cleanType = Security\h($row["protype"]);
        $cleanDesc = Security\h($row["description"]);
        
        $show_product .= '
        <div class="product-item">
            <a href="viewimg.php?id=' . (int)$row["id"] . '">
                <div class="img-container">
                    <img src="imgs/' . Security\h($row["prourl"]) . '" alt="' . $cleanName . '" ' . $loading_attr . ' ' . $fetch_priority . '>
                </div>
                <div class="product-info">
                    <h5>' . $cleanName . '</h5>
                    <p>' . $cleanDesc . '</p>
                </div>
            </a>
            <button class="add-to-cart-btn" title="Thêm vào giỏ" onclick="addToCart(event, \'' . (int)$row["id"] . '\', \'' . $cleanName . '\', \'' . Security\h($row["prourl"]) . '\', \'' . $cleanType . '\')">
                <i class="fa fa-cart-plus"></i>
            </button>
        </div>';
    }
} else {
    $show_product = '<div class="alert alert-info w-100">Không tìm thấy kết quả phù hợp cho: <b>' . Security\h($searchtext) . '</b></div>';
}

// Pagination Logic
$stmt_count = $link->prepare("SELECT COUNT(id) AS total FROM products WHERE proname REGEXP ? OR protype REGEXP ?");
$stmt_count->bind_param("ss", $regex_pattern, $regex_pattern);
$stmt_count->execute();
$row_count = $stmt_count->get_result()->fetch_assoc();
$total = $row_count['total'];
$pages = ceil($total / $limit);
$netxpage = $page < $pages ? $page + 1 : $pages;
$previouspage = $page > 1 ? $page - 1 : $page;

$pageslist = '<a href="searchpage.php?page=' . $previouspage . '&search=' . urlencode($searchtext) . '">&laquo;</a>';
for ($i = 1; $i <= $pages; $i++) {
    $activeClass = ($page == $i) ? 'class="active"' : '';
    $pageslist .= '<a href="searchpage.php?page=' . $i . '&search=' . urlencode($searchtext) . '" ' . $activeClass . '>' . $i . '</a>';
}
$pageslist .= '<a href="searchpage.php?page=' . $netxpage . '&search=' . urlencode($searchtext) . '">&raquo;</a>';

// Sidebar / Nav Categories
$show_protype = "";
$show_protypelist = "";
$res_cat = $link->query("SELECT protype FROM products GROUP BY protype");
if ($res_cat && $res_cat->num_rows > 0) {
    while ($row_cat = $res_cat->fetch_assoc()) {
        $safeType = Security\h($row_cat["protype"]);
        $urlType = urlencode($row_cat["protype"]);
        $show_protype .= '<a href="protype.php?id=' . $urlType . '"><p class="nav-link type-link type" value="' . $safeType . '">' . $safeType . '</p></a>';
        $show_protypelist .= '<li><a href="protype.php?id=' . $urlType . '">' . $safeType . '</a></li>';
    }
}

include "header_site.php";
?>
<div class="listcnc" style="margin-top: 0;">
    <div class="container">
        <div class="row">
            <div class="col-lg-9">
                <h4 class="mb-4">Kết quả tìm kiếm cho: "<?php echo Security\h($searchtext); ?>"</h4>
                <div id="products">
                    <?php echo $show_product ?>
                </div>
                <?php if ($pages > 1): ?>
                <div class="paginationcenter">
                    <div class="pagination">
                        <?php echo $pageslist ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php include "sidebar_categories.php"; ?>
        </div>
    </div>
</div>

<?php include "footer_site.php"; ?>
</body>
</html>
