<?php
if (!defined('MT_CNC_AUTH')) exit('Access Denied');
/**
 * Universal Frontend Header
 * Handles SEO, Marquee Slogan, and Navigation.
 * Managed centrally in home/header_site.php.
 */

// Determine dynamic page title and description
$displayId = isset($id) ? Security\h($id) : "";
$pageTitle = !empty($displayId) ? $displayId . " - " . SITE_NAME : SITE_NAME . " - Kiến tạo không gian tâm linh tinh xảo";
$pageDesc = !empty($displayId) ? "Danh mục " . $displayId . ". " . SITE_DESCRIPTION : SITE_DESCRIPTION;
$canonicalUrl = "https://mautranhcnc.com/home/" . basename($_SERVER['PHP_SELF']) . (isset($id) ? "?id=" . urlencode($id) : "");
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo $pageDesc; ?>">
    <meta name="keywords" content="<?php echo !empty($displayId) ? $displayId . ", " : ""; ?><?php echo SITE_KEYWORDS; ?>">
    <meta name="author" content="<?php echo SITE_NAME; ?>">
    <link rel="canonical" href="<?php echo $canonicalUrl; ?>">

    <!-- Open Graph / Social SEO -->
    <meta property="og:type" content="<?php echo isset($id) ? 'article' : 'website'; ?>">
    <meta property="og:title" content="<?php echo $pageTitle; ?>">
    <meta property="og:description" content="<?php echo $pageDesc; ?>">
    <meta property="og:image" content="<?php echo OG_IMAGE_URL; ?>">
    <meta property="og:url" content="<?php echo $canonicalUrl; ?>">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo SITE_LOGO; ?>">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <!-- Render-Blocking CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <script src="js/cart.js?v=<?php echo time(); ?>"></script>
</head>

<body>

    <!-- Fun Slogan Marquee (Centralized from config_site.php) -->
    <div class="announcement-bar">
        <div class="marquee-container">
            <?php 
            if (defined('SITE_SLOGANS') && is_array(SITE_SLOGANS)) {
                // Loop 1
                foreach (SITE_SLOGANS as $slogan) {
                    echo '<span class="slogan-item">' . htmlspecialchars($slogan) . '</span>';
                }
                // Loop 2 for continuous scrolling
                foreach (SITE_SLOGANS as $slogan) {
                    echo '<span class="slogan-item">' . htmlspecialchars($slogan) . '</span>';
                }
            }
            ?>
        </div>
    </div>

    <!-- Navigation Header -->
    <header class="headerr">
        <!-- Main Nav -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-transparent">
            <div class="container d-flex align-items-center justify-content-between">
                <!-- Brand -->
                <a class="navbar-brand d-flex align-items-center" href="index.php" style="gap: 10px;">
                    <img src="<?php echo SITE_LOGO; ?>" alt="Logo" style="height: 35px; width: auto;">
                    <span style="font-weight: 700; font-size: 1.1rem; color: #fff; letter-spacing: 0.5px;">
                        <?php echo mb_strtoupper(SITE_NAME, 'UTF-8'); ?>
                    </span>
                </a>

                <!-- Right Controls -->
                <div class="d-flex align-items-center">
                    <!-- Shopping Cart -->
                    <a class="nav-link p-2 cart-nav-icon d-lg-none" href="cart.php" style="color: #fff; position: relative;">
                        <i class="fa fa-shopping-cart" style="font-size: 1.35rem;"></i>
                        <span class="badge badge-pill badge-danger cart-badge cart-badge-count" id="cartCount" style="position: absolute; top: 0; right: 0; font-size: 0.65rem; display: none;">0</span>
                    </a>

                    <!-- Search Trigger (Mobile) -->
                    <button class="btn btn-link p-2 d-lg-none" id="openSearch" style="color: #fff; border: none; outline: none; box-shadow: none;">
                        <i class="fa fa-search" style="font-size: 1.25rem;"></i>
                    </button>

                    <!-- Menu Modal Toggler (Mobile) -->
                    <button class="btn btn-link p-2 ml-1 d-lg-none" id="btnhide" type="button" data-toggle="modal" data-target="#menuModal" style="color: #fff; border: none; outline: none; box-shadow: none;">
                        <i class="fa fa-bars" style="font-size: 1.4rem;"></i>
                    </button>

                    <!-- Desktop Navigation -->
                    <div class="collapse navbar-collapse" id="collapsibleNavbar">
                                                <ul class="navbar-nav ml-auto align-items-center">
                            <li class="nav-item ml-3">
                                <a class="nav-link text-white" href="index.php">Trang chủ</a>
                            </li>
                            <li class="nav-item ml-3">
                                <a class="nav-link text-white" href="#" data-toggle="modal" data-target="#menuModal">Liên hệ</a>
                            </li>
                            <li class="nav-item ml-3 d-none d-lg-block">
                                <form action="searchpage.php" method="get" class="form-inline m-0">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control form-control-sm rounded-pill bg-dark border-secondary text-white px-3" placeholder="Tìm mẫu..." style="width: 150px; font-size: 0.85rem;">
                                    </div>
                                </form>
                            </li>
                            <!-- Desktop Cart -->
                            <li class="nav-item ml-3 d-none d-lg-block">
                                <a class="nav-link p-0 cart-nav-icon" href="cart.php" style="color: #fff; position: relative;">
                                    <i class="fa fa-shopping-cart" style="font-size: 1.35rem;"></i>
                                    <span class="badge badge-pill badge-danger cart-badge cart-badge-count" id="cartCountDesktop" style="position: absolute; top: -8px; right: -12px; font-size: 0.65rem; display: none;">0</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Integrated Category Scroll Bar (Always immediately below nav) -->
        <?php if (isset($show_protype) && !empty($show_protype)): ?>
        <div class="typepro-container" style="border-top: 1px solid rgba(255,255,255,0.08);">
            <div class="typepro" id="typepro" style="background: transparent;">
                <div class="typeprochild" id="typeprochild">
                    <?php echo $show_protype ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </header>

    <!-- Compact Search Overlay (Mobile Slide-down) -->
    <div class="search-overlay" id="searchOverlay">
        <form action="searchpage.php" method="get">
            <input type="text" name="search" placeholder="Tìm mẫu..." autofocus id="overlaySearchInput">
            <div class="close-search" id="closeSearch">&times;</div>
        </form>
    </div>
