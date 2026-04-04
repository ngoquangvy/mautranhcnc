<?php

require_once "../includes/connectdb.php";
$searchtext = mb_substr(trim($_GET['search'] ?? ''), 0, 50); // Keep search in URL to avoid form resubmission
if (isset($_POST['search'])) {
    $searchtext = mb_substr(trim($_POST['search']), 0, 50); // Giới hạn 50 ký tự để chống ReDoS
}
// set limit and offset for pagination
$limit = 24; // number of records per page
if (isset($_GET['page'])) {
    $page = (int)$_GET['page']; // current page number
    $searchtext = mb_substr($_GET['search'] ?? '', 0, 50);
} else {
    $page = 1; // default page number
}
// -------------------------------------------------------------
// GIẢI THÍCH BẢO MẬT: SEARCH THROTTLE (ANTI-DoS)
// -------------------------------------------------------------
// Vì tìm kiếm sử dụng REGEXP (biểu thức chính quy), nó tốn CPU hơn 
// so với LIKE thông thường. Kẻ xấu có thể bắn hàng nghìn request search 
// liên tục để làm treo máy chủ (Denial of Service).
// GIẢI PHÁP: Giới hạn tần suất tìm kiếm mỗi phút của người dùng.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$now = time();
$window = 60; // 60 giây
$max_requests = 10; // 10 lần search

if (!isset($_SESSION['search_history'])) {
    $_SESSION['search_history'] = [];
}

// Xóa các request cũ ngoài cửa sổ 60s
$_SESSION['search_history'] = array_filter($_SESSION['search_history'], function($t) use ($now, $window) {
    return $t > ($now - $window);
});

if (count($_SESSION['search_history']) >= $max_requests) {
    die("Bạn đang tìm kiếm quá nhanh. Vui lòng thử lại sau 1 phút.");
}

$_SESSION['search_history'][] = $now;
// -------------------------------------------------------------

// Calculate the offset
$offset = ($page - 1) * $limit;

/**
 * Chuyển đổi văn bản tìm kiếm thành pattern REGEXP để tìm kiếm tiếng Việt không dấu/có dấu 
 */
function vi_to_regex($str) {
    if (empty($str)) return ".*";
    // Thoát các ký tự đặc biệt của regex
    $str = preg_quote($str, '/');
    $map = [
        'a' => '[aàáảãạăằắẳẵặâầấẩẫậ]',
        'e' => '[eèéẻẽẹêềếểễệ]',
        'i' => '[iìíỉĩị]',
        'o' => '[oòóỏõọôồốổỗộơờớởỡợ]',
        'u' => '[uùúủũụưừứửữự]',
        'y' => '[yỳýỷỹỵ]',
        'd' => '[dđ]',
        'A' => '[AÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬ]',
        'E' => '[EÈÉẺẼẸÊỀẾỂỄỆ]',
        'I' => '[IÌÍỈĨỊ]',
        'O' => '[OÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢ]',
        'U' => '[UÙÚỦŨỤƯỪỨỬỮỰ]',
        'Y' => '[YỲÝỶỸỴ]',
        'D' => '[DĐ]'
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

// Query the database using REGEXP for Vietnamese search support
$stmt = $link->prepare("SELECT * FROM products WHERE proname REGEXP ? OR protype REGEXP ? order by id DESC LIMIT ? OFFSET ?");
if ($stmt) { // check if prepare succeeded
    $stmt->bind_param("ssii", $regex_pattern, $regex_pattern, $limit, $offset);
    // execute and fetch results
} else { // handle prepare error
    echo "Prepare failed: " . $link->error;
}
$stmt->execute();
$result = $stmt->get_result();
$show_product = "";
// Check if any results were found
if ($result->num_rows > 0) {
    $atf_counter = 0;
    // Output data of each row
    while ($row = $result->fetch_assoc()) {
        /* 
           HEURISTIC SAFE RANGE (LIMIT 24):
           Cụm Top mỗi cột Masonry (0, 6, 12, 18) kết hợp dải an toàn [+1, -1] 
           để bắt được các ảnh Above The Fold bất kể trình duyệt tự cân bằng.
        */
        $atf_indices = [0, 1, 5, 6, 7, 11, 12, 13, 17, 18, 19];
        $is_atf = in_array($atf_counter, $atf_indices);
        $loading_attr = ($is_atf) ? "" : "loading=\"lazy\"";
        $fetch_priority = ($atf_counter === 0) ? "fetchpriority=\"high\"" : "";
        $atf_counter++;
        $cleanName = Security\h($row["proname"]);
        $cleanType = Security\h($row["protype"]);
        $cleanDesc = Security\h($row["description"]);
        $show_product = $show_product . '
        <div class="product-item">
            <a href="../home/viewimg.php?id=' .  (int)$row["id"] . '">
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
}


// pagination begin
$stmt = $link->prepare("SELECT COUNT(id) AS total FROM products WHERE proname REGEXP ? OR protype REGEXP ?");
if ($stmt) { // check if prepare succeeded
    $stmt->bind_param("ss", $regex_pattern, $regex_pattern);
    // execute and fetch results
} else { // handle prepare error
    echo "Prepare failed: " . $link->error;
}
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total = $row['total']; // total number of records
// calculate total number of pages for pagination
$pages = ceil($total / $limit); // total number of pages
$netxpage = $page < $pages ? $page + 1 : $pages;
$previouspage = $page > 1 ? $page - 1 : $page;
$pageslist = '<a href="searchpage.php?page=' . $previouspage . '&search=' . urlencode($searchtext) . '">&laquo;</a>';
for ($i = 1; $i <= $pages; $i++) {
    if ($page == $i) {
        $pageslist .= '<a href="#" class="active">' . $i . '</a>';
    } else {
        $pageslist .= '<a href="searchpage.php?page=' . $i . '&search=' . urlencode($searchtext) . '">' . $i . '</a>';
    }
}

$pageslist = $pageslist . '
    <a href="searchpage.php?page=' . $netxpage . '&search=' . urlencode($searchtext) . '">&raquo;</a>
    ';
// pagination end
$show_protype = "";
$sql_fr1 = "SELECT protype from products group by protype";

$result_fr1 = $link->query($sql_fr1);
if (
    $result_fr1 && ($result_fr1->num_rows > 0)
) {
    while ($row_fr1 = mysqli_fetch_assoc($result_fr1)) {
        // -------------------------------------------------------------
        // GIẢI THÍCH BẢO MẬT: ĐỒNG BỘ HÓA PHÒNG THỦ (SECURE SYNC)
        // -------------------------------------------------------------
        // Tại sao lại phải sửa lỗi XSS ở đây khi trang chủ đã sửa?
        // Vì trong một dự án lớn, một UI (Sidebar danh mục) có thể xuất hiện 
        // ở nhiều file khác nhau. Nếu chỉ vá trang chủ mà quên các trang 
        // con (Protype, Search) thì Hacker vẫn có thể tấn công từ trang đó.
        
        $safeType = Security\h($row_fr1["protype"]);
        $urlType = urlencode($row_fr1["protype"]);

        $show_protype = $show_protype . ' 
                <a href="../home/protype.php?id=' .  $urlType . '">
                  <p class="nav-link type-link type" value="' . $safeType . '" > ' . $safeType . ' </p>
                  </a>
              ';
        // -------------------------------------------------------------
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mẫu CNC</title>
    <script src="js/jquery.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <script src="js/bootstrap.min.js"></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --accent-color: #10b981;
            --bg-light: #f8f9fa;
            --text-dark: #2d3436;
            --card-shadow: 0 4px 15px rgba(0,0,0,0.08);
            --hover-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Modern Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 1px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #999; }

        /* --- PREMIUM HEADER UPGRADE --- */
        .headerr {
            z-index: 1050;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: auto !important;
            bottom: auto !important;
            background: rgba(33, 37, 41, 0.85) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .headerr.scrolled {
            background: rgba(20, 20, 20, 0.95) !important;
            padding: 5px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .navbar-brand { 
            font-weight: 800; 
            letter-spacing: -1.2px;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease;
        }
        .navbar-brand:hover { transform: scale(1.02); }
        .navbar-brand img {
            height: 45px !important;
            margin-right: 12px;
            filter: drop-shadow(0 0 8px rgba(39, 174, 96, 0.3));
            animation: logoPulse 4s infinite ease-in-out;
            transition: all 0.3s ease;
        }

        @keyframes logoPulse {
            0%, 100% { filter: drop-shadow(0 0 5px rgba(39, 174, 96, 0.3)); transform: scale(1); }
            50% { filter: drop-shadow(0 0 15px rgba(39, 174, 96, 0.6)); transform: scale(1.05); }
        }

        .nav-link {
            position: relative;
            font-weight: 500;
            margin: 0 10px;
            transition: color 0.3s;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--accent-color);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        .nav-link:hover::after, .nav-item.active .nav-link::after {
            width: 80%;
        }

        /* Mobile Search Toggle */
        .search-trigger {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.2rem;
            padding: 8px;
            display: none; /* Desktop hidden */
            transition: opacity 0.3s;
        }
        .search-trigger:hover { opacity: 0.7; }

        /* Search Overlay - Apple Style */
        .search-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(15px);
            z-index: 2000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 20px;
        }
        .search-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .search-overlay .close-search {
            position: absolute;
            top: 30px;
            right: 30px;
            font-size: 2rem;
            color: #fff;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .search-overlay .close-search:hover { transform: rotate(90deg); color: var(--accent-color); }
        .search-overlay input {
            width: 80%;
            max-width: 600px;
            background: transparent;
            border: none;
            border-bottom: 2px solid rgba(255,255,255,0.2);
            color: #fff;
            font-size: 2.5rem;
            font-weight: 300;
            text-align: center;
            padding: 20px;
            transition: border-color 0.3s;
        }
        .search-overlay input:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        /* Full-screen Menu (Apple-style) */
        @media (max-width: 768px) {
            .navbar-collapse {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100vh;
                background: rgba(0, 0, 0, 0.98);
                backdrop-filter: blur(20px);
                display: flex !important;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                transform: translateY(-100%);
                transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
                z-index: 1040;
                overflow: hidden;
            }
            .navbar-collapse.show { transform: translateY(0); }
            .navbar-nav { text-align: center; }
            .navbar-nav .nav-link { 
                font-size: 1.8rem; 
                margin: 15px 0; 
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.4s ease;
            }
            .navbar-collapse.show .nav-link { opacity: 1; transform: translateY(0); }
            
            #ser-form { display: none; } /* Hide default search on mobile */
            .search-trigger { display: block; }
            
            .navbar-brand span { display: none; } /* Logo Icon only on tiny screens */
            .navbar-brand img { height: 35px !important; margin-right: 0; }
        }

        .navbar-brand { font-weight: 700; letter-spacing: -0.5px; }

        .ser-input {
            border-radius: 20px;
            background: rgba(255,255,255,0.1) !important;
            border: 1px solid rgba(255,255,255,0.2);
            color: white !important;
            padding: 5px 20px;
            transition: all 0.3s;
        }
        .ser-input:focus {
            background: rgba(255,255,255,0.2) !important;
            box-shadow: none;
            border-color: var(--accent-color);
        }

        .typepro {
            background: #2d3436;
            padding: 10px 0;
            overflow-x: auto;
            white-space: nowrap;
            display: flex;
            align-items: center;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
            cursor: grab;
            user-select: none;
        }
        .typepro::-webkit-scrollbar { display: none; }
        .typepro.active { cursor: grabbing; scale: 1; }

        /* Category Bar Fade Effect */
        .typepro-container {
            position: relative;
            background: #2d3436;
        }
        .typepro-container::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 50px;
            background: linear-gradient(to right, transparent, #2d3436);
            pointer-events: none;
            z-index: 2;
        }

        .type-link {
            color: #dfe6e9;
            text-decoration: none;
            padding: 6px 16px;
            margin: 0 5px;
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: 20px;
            transition: all 0.3s;
            border: 1px solid transparent;
            display: inline-block;
        }
        .type-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        /* Masonry Grid Layout */
        .listcnc { padding-top: 160px; padding-bottom: 60px; }
        
        #products {
            display: block;
            column-count: 4;
            column-gap: 20px;
        }

        @media (max-width: 1200px) { #products { column-count: 3; } }
        @media (max-width: 992px) { #products { column-count: 2; } }
        @media (max-width: 576px) { #products { column-count: 2; column-gap: 12px; } }

        /* Product Card Redesign */
        .product-item {
            display: inline-block;
            width: 100%;
            break-inside: avoid;
            margin-bottom: 20px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            position: relative;
        }
        @media (max-width: 576px) { .product-item { margin-bottom: 12px; } }

        .product-item:hover {
            transform: translateY(-8px);
            box-shadow: var(--hover-shadow);
        }

        .product-item a { text-decoration: none; color: inherit; }

        .img-container {
            width: 100%;
            overflow: hidden;
            background: #f1f1f1;
        }
        .img-container img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.6s ease;
        }
        .product-item:hover .img-container img { transform: scale(1.08); }

        .product-info { padding: 15px; background: white; }
        .product-info h5 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-info h7 {
            font-size: 0.8rem;
            color: #636e72;
            display: block;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .product-info p {
            font-size: 0.85rem;
            color: #b2bec3;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
        }

        /* Pagination Premium */
        .paginationcenter { margin-top: 40px; }
        .pagination a {
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            display: inline-block;
            background: white;
            border-radius: 50%;
            margin: 0 4px;
            color: var(--primary-color);
            box-shadow: var(--card-shadow);
            transition: all 0.3s;
            font-weight: 600;
        }
        .pagination a.active { background: var(--accent-color); color: white; border: none; }
        .pagination a:hover:not(.active) { background: var(--primary-color); color: white; }

        /* Floating Contact Premium */
        .phone {
            position: fixed;
            bottom: 30px;
            right: 25px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .phone a {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .phone a::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            z-index: -1;
            animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }
        /* Zalo color */
        .phone a:nth-child(1)::before { background: #0084ff; } 
        /* Facebook color */
        .phone a:nth-child(2)::before { background: #1877f2; animation-delay: 1s; } 

        .phone img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: transform 0.3s;
            z-index: 2;
            background: white;
            padding: 2px;
        }
        .phone a:hover { transform: translateY(-5px) scale(1.05); }
        .phone a:hover::before { animation: none; opacity: 0; }

        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 0.8; }
            80% { transform: scale(1.6); opacity: 0; }
            100% { transform: scale(1.6); opacity: 0; }
        }
    </style>
    <!-- Cart System -->
    <link href="css/cart.css" rel="stylesheet">
    <script src="js/cart.js"></script>
</head>

<body class="bg-light">
    <header class="headerr">
        <nav class="navbar navbar-expand-md navbar-dark bg-dark d-flex" id="navbar">
            <div class="container">
                <a class="navbar-brand" href="../home">
                    <img src="imgs/logo/mt_logo.png" alt="Logo">
                    <span>Mẫu CNC</span>
                </a>
                
                <div class="d-flex align-items-center">
                    <!-- Mobile Cart Icon -->
                    <a class="cart-nav-icon mr-3 d-md-none" href="cart.php">
                        <i class="fa fa-shopping-cart"></i>
                        <span class="cart-badge-count">0</span>
                    </a>
                    
                    <button class="search-trigger" id="openSearch">
                        <i class="fa fa-search"></i>
                    </button>
                    
                    <button class="navbar-toggler ml-2" id="btnhide" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>

                <div class="collapse navbar-collapse" id="navbarsExampleDefault">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item active">
                            <a class="nav-link" href="../home">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="https://www.facebook.com/thien.bui.12327608">FaceBook</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href=" https://zalo.me/0338790560">ZALO</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link contactt" id="contact">Contact</a>
                        </li>
                        <li class="nav-item d-none d-md-flex align-items-center ml-2">
                            <a class="nav-link cart-nav-icon" href="cart.php" style="padding: 0;">
                                <i class="fa fa-shopping-cart" style="font-size: 1.4rem;"></i>
                                <span class="cart-badge-count">0</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <form action="searchpage.php" method="get" class="ml-auto d-none d-md-block" id="ser-form">
                    <div class="form-row">
                        <div class="col-8">
                            <input type="text" class="form-control rounded-pill bg-dark ser-input" id="ser-input" name="search" placeholder="Search..." value="<?php echo Security\h($searchtext); ?>">
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </nav>
        
        <div class="typepro-container">
            <div class="typepro" id="typepro">
                <div class="typeprochild" id="typeprochild">
                    <?php echo $show_protype ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Search Overlay -->
    <div class="search-overlay" id="searchOverlay">
        <div class="close-search" id="closeSearch">&times;</div>
        <form action="searchpage.php" method="get">
            <input type="text" name="search" placeholder="Type to search..." autofocus id="overlaySearchInput" value="<?php echo Security\h($searchtext); ?>">
        </form>
    </div>

    <!-- end slide -->
    <div class="listcnc">
        <div class="container">
            <div id="products">
                <?php echo $show_product ?>
            </div>
        </div>

        <!-- Floating Contact -->
        <div class="phone">
            <a href="https://zalo.me/0338790560" title="Zalo Contact"><img src="imgs/logo/zalo.png" alt="Zalo"></a>
            <a href="https://www.facebook.com/thien.bui.12327608" title="Facebook Contact"><img src="imgs/logo/fb.png" alt="Facebook"></a>
        </div>

            </div>
        </div>

        <div class="paginationcenter">
            <div class="pagination">
                <?php echo $pageslist ?>
            </div>
        </div>
        <!-- Footer -->
        <footer class="bg-dark text-center text-lg-start text-white">
            <!-- Grid container -->
            <div class="container p-4">
                <!--Grid row-->
                <div class="row">
                    <!--Grid column-->
                    <div class="col-lg-6 col-md-12 mb-4 mb-md-0">
                        <img class="logo-footer mb-4" src="imgs/logo/mt_logo.png" alt="MauTranhCNC Logo" style="max-width: 150px;">
                        <h5 class="text-uppercase">Mẫu Tranh CNC</h5>
                        <p>Quản Lý: Thiện Bùi </p>
                        <p>Phone: 0338790560 </p>
                        <p>FaceBook: facebook.com/thien.bui.12327608 </p>
                        <p>Zalo:0338790560 </p>
                        <p>Gmail:buiminhthien96@gmail.com </p>
                    </div>
                    <!--Grid column-->

                    <!--Grid column-->
                    <div class="col-lg-3 col-md-6 mb-4 mb-md-0">

                        <p id="tktyc">Thiết kế theo yêu cầu </p>

                    </div>
                    <!--Grid column-->

                    <!--Grid column-->

                    <!--Grid column-->
                </div>
                <!--Grid row-->
            </div>
            <!-- Grid container -->

            <!-- Copyright -->
            <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2);">
                <a class="text-dark" href="">MauTranhCNC</a>
            </div>
            <!-- Copyright -->
        </footer>
        <!-- Footer -->

</body>

<script src="js/my.js"></script>


</html>
