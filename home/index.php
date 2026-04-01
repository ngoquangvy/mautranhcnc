<?php
require_once "../includes/connectdb.php";
require_once "../includes/cache.php";

if (isset($_GET['page'])) {
    $page = $_GET['page']; // current page number
} else {
    $page = 1; // default page number
}

$cacheKey = "home_index_p" . $page;
$cachedData = FileCache::get($cacheKey);

if ($cachedData) {
    $show_product = $cachedData['show_product'];
    $pageslist = $cachedData['pageslist'];
    $show_protype = $cachedData['show_protype'];
    $show_protypelist = $cachedData['show_protypelist'];
} else {
    $limit = 24; // number of records per page
    $offset = ($page - 1) * $limit;
    $show_product = "";
    $sql_fr1 = "SELECT protype from products GROUP BY protype order by id DESC LIMIT $limit OFFSET $offset";
    $result_fr1 = $link->query($sql_fr1);
    if (
        $result_fr1 && ($result_fr1->num_rows > 0)
    ) {
        while ($row_fr1 = mysqli_fetch_assoc($result_fr1)) {
            $sql_fr = 'SELECT * from products where protype="' . $row_fr1["protype"] . '" order by id DESC';
            $result_fr = $link->query($sql_fr);
            if ($result_fr && ($result_fr->num_rows > 0)) {
                $row_fr = mysqli_fetch_assoc($result_fr);
                $show_product = $show_product . '
                  <div class="product-item" value="' . $row_fr1["protype"] . '">
                      <a href="../home/protype.php?id=' .  $row_fr1["protype"] . '">
                          <div class="img-container">
                              <img src="imgs/' . $row_fr["prourl"] . '" alt="' . $row_fr["proname"] . '" loading="lazy">
                          </div>
                          <div class="product-info">
                              <h5>' . $row_fr["proname"] . '</h5>
                              <p>' . $row_fr["description"] . '</p>
                          </div>
                      </a>
                  </div>
                  ';
            }
        }
    }

    // pagination begin
    $stmt = $link->prepare("SELECT COUNT(DISTINCT protype) AS total FROM products");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total = $row['total']; // total number of records
    // calculate total number of pages for pagination
    $pages = ceil($total / $limit); // total number of pages
    $netxpage = $page < $pages ? $page + 1 : $page;
    $previouspage = $page > 1 ? $page - 1 : $page;
    $pageslist = '<a href="?page=' . $previouspage . '">&laquo;</a>';
    for ($i = 1; $i <= $pages; $i++) {
        if ($page == $i) {
            $pageslist = $pageslist . '
            <a href="#" class="active">' . $i . '</a>
        ';
        } else {
            $pageslist = $pageslist . '
            <a href="?page=' . $i . '">' . $i . '</a>
            ';
        }
    }
    $pageslist = $pageslist . '
        <a href="?page=' . $netxpage . '">&raquo;</a>
        ';
    // pagination end

    $show_protype = "";
    $show_protypelist = "";
    $sql_fr1 = "SELECT protype from products group by protype";
    $result_fr1 = $link->query($sql_fr1);
    if (
        $result_fr1 && ($result_fr1->num_rows > 0)
    ) {
        while ($row_fr1 = mysqli_fetch_assoc($result_fr1)) {
            // $rf=$row_fr1["id"];
            $show_protype = $show_protype . ' 
                    <a href="../home/protype.php?id=' .  $row_fr1["protype"] . '">
                      <p class="nav-link type-link type" value="' . $row_fr1["protype"] . '" > ' . $row_fr1["protype"] . ' </p>
                    </a>
                  ';
             $show_protypelist = $show_protypelist . ' <li>
                    <a " href="../home/protype.php?id=' .  $row_fr1["protype"] . '">
                    ' .  $row_fr1["protype"] . '
                      </a>
                  </li>';
        }
    }

    // Save to cache
    FileCache::set($cacheKey, [
        'show_product' => $show_product,
        'pageslist' => $pageslist,
        'show_protype' => $show_protype,
        'show_protypelist' => $show_protypelist
    ], FileCache::HOME_TTL);
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mẫu CNC</title>
    <link rel="shortcut icon" href="imgs/logo/mt_logo.png">
    <script src="js/jquery.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <script src="js/bootstrap.min.js"></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --accent-color: #27ae60;
            --bg-light: #f8f9fa;
            --text-dark: #2d3436;
            --card-shadow: 0 4px 15px rgba(0,0,0,0.08);
            --hover-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Modern Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #999; }

        /* Navigation Header */
        .headerr {
            z-index: 99;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: auto !important;
            bottom: auto !important;
            background: rgba(33, 37, 41, 0.95) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
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

        /* Type Filter Bar */
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
        @media (max-width: 992px) { #products { column-count: 2; } .sidebar-wrapper { display: none; } }
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
        .product-item:hover .img-container img {
            transform: scale(1.08);
        }

        .product-info {
            padding: 15px;
            background: white;
        }
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

        /* Sidebar Styles */
        .sidebar-heading {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 20px;
            color: var(--primary-color);
            position: relative;
            padding-bottom: 10px;
        }
        .sidebar-heading:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 40px;
            height: 3px;
            background: var(--accent-color);
        }

        .ultypelist {
            list-style: none;
            padding: 0;
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        .ultypelist li a {
            display: block;
            padding: 12px 20px;
            color: var(--text-dark);
            text-decoration: none;
            border-bottom: 1px solid #f1f1f1;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .ultypelist li a:hover {
            background: #f8f9fa;
            color: var(--accent-color);
            padding-left: 25px;
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
        .pagination a.active {
            background: var(--accent-color);
            color: white;
            border: none;
        }
        .pagination a:hover:not(.active) {
            background: var(--primary-color);
            color: white;
        }

        /* Floating Contact */
        .phone {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
        .phone img {
            width: 50px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
            transition: transform 0.3s;
        }
        .phone img:hover { transform: scale(1.1) rotate(5deg); }
    </style>

</head>

<body class="bg-light">
    <header class="headerr">
        <nav class="navbar navbar-expand-md navbar-dark bg-dark d-flex" id="navbar">
            <div class="container">
                <a class="navbar-brand" href="../home" class="text-white"><img src="imgs/logo/mt_logo.png" alt="Logo" style="height: 40px; margin-right: 10px;"> Mẫu CNC</a>
                <button class="navbar-toggler " id="btnhide" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
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
                    </ul>
                </div>
                <form action="searchpage.php" method="post">
                    <div class="form-row">
                        <div class="col-8">
                            <input type="text" class="form-control rounded-0 bg-dark ser-input" id="ser-input" name="search" placeholder="Search...">
                        </div>
                        <div class="col-4">
                            <input type="submit" value="Search" class="btn btn-primary">
                        </div>
                    </div>
                </form>
            </div>
        </nav>
        <!-- <nav class="navbar navbar-expand-md navbar-dark bg-dark d-flex" id="navbar"> -->
        <!-- <div class="container">
          <a class="navbar-brand text-white">Loại Mẫu:</a>
            <button class="navbar-toggler " type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button> -->

        <div class="typepro" id="typepro">
            <!-- <div class="dau">
                <button onclick="left()"><img src="imgs/logo/left-arrow.png" alt="buttonpng" style="width:1px; height: 10px;" /></button>
            </div> -->
            <div class="typeprochild" id="typeprochild">
                <?php echo $show_protype ?>
            </div>
            <!-- <div class="cuoi">
                <button onclick="right()"><img src="imgs/logo/right-arrow.png" alt="buttonpng" style="width:10px; height: 10px;" /></button>
            </div> -->
        </div>
        <!-- </div> -->
        <!-- </nav> -->
    </header>


    <!-- end slide -->
   <div class="listcnc">
        <div class="container">
            <div class="row">
                <!-- Column for existing content -->
                <div class="col-lg-9">
                    <div id="products">
                        <?php echo $show_product ?>
                    </div>
                </div>
                <!-- Sidebar -->
                <div class="col-lg-3 sidebar-wrapper" id="sidebar">
                    <h5 class="sidebar-heading">Phân loại mẫu</h5>
                    <ul class="ultypelist">
                        <?php echo $show_protypelist ?>
                    </ul>
                </div>
            </div>
        </div>

            </div>
        </div>

        <!-- Floating Contact -->
        <div class="phone">
            <a href="https://zalo.me/0338790560" title="Zalo Contact"><img src="imgs/logo/zalo.png" alt="Zalo"></a>
            <a href="https://www.facebook.com/thien.bui.12327608" title="Facebook Contact"><img src="imgs/logo/fb.png" alt="Facebook"></a>
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