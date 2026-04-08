<?php
require_once "../includes/connectdb.php";
require_once "../includes/config_site.php";

// Set authentication constant for includes
if (!defined('MT_CNC_AUTH')) define('MT_CNC_AUTH', true);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$show_product = "";
$proname = "Chi tiết mẫu";
$protype = "";

// Security: Prepared Statement for product details
$stmt = $link->prepare("SELECT prourl, proname, protype FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result_fr1 = $stmt->get_result();

if ($result_fr1 && ($result_fr1->num_rows > 0)) {
    while ($row_fr1 = $result_fr1->fetch_assoc()) {
        $show_product = $row_fr1["prourl"];
        $proname = $row_fr1["proname"];
        $protype = $row_fr1["protype"];
    }
}
$stmt->close();

// Prepare dynamic meta for header_site.php
// header_site.php uses $pageTitle and $pageDesc if defined
$pageTitle = htmlspecialchars($proname) . " - " . SITE_NAME;
$pageDesc = "Xem chi tiết mẫu " . htmlspecialchars($proname) . " thuộc danh mục " . htmlspecialchars($protype) . ". Mẫu CNC chất lượng cao, sắc nét.";

include "header_site.php";
?>
<style>
    /* Specialized styles for Fullscreen Viewer */
    body { background-color: #0f172a; overflow-x: hidden; }
    main { position: relative; height: 80vh; margin-top: 20px; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
    #openseadragon1 { width: 100%; height: 100%; background-color: #000; }
    
    .viewer-info-bar {
        background: rgba(255,255,255,0.05);
        backdrop-filter: blur(10px);
        border-radius: 50px;
        padding: 10px 25px;
        margin-top: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid rgba(255,255,255,0.1);
    }
    
    .meta-text h2 { font-size: 1.2rem; margin: 0; font-weight: 700; color: #fff; }
    .meta-text span { font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; }
    
    .viewer-actions .btn-cart-view {
        background: #10b981;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        transition: 0.3s;
    }
    .viewer-actions .btn-cart-view:hover { background: #059669; transform: scale(1.05); }
</style>

<div class="container" style="margin-top: 100px; margin-bottom: 50px;">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-transparent p-0" style="font-size: 0.9rem;">
            <li class="breadcrumb-item"><a href="index.php" class="text-muted"><i class="fa fa-home"></i> Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="protype.php?id=<?php echo urlencode($protype); ?>" class="text-muted"><?php echo Security\h($protype); ?></a></li>
            <li class="breadcrumb-item active text-white" aria-current="page"><?php echo Security\h($proname); ?></li>
        </ol>
    </nav>

    <main>
        <div id="openseadragon1"></div>
    </main>

    <div class="viewer-info-bar">
        <div class="meta-text">
            <span>Danh mục: <?php echo Security\h($protype); ?></span>
            <h2><?php echo Security\h($proname); ?></h2>
        </div>
        <div class="viewer-actions">
            <button class="btn-cart-view" onclick="addToCart(event, '<?php echo $id; ?>', '<?php echo addslashes($proname); ?>', '<?php echo addslashes($show_product); ?>', '<?php echo addslashes($protype); ?>')">
                <i class="fa fa-cart-plus mr-2"></i>Thêm vào giỏ
            </button>
        </div>
    </div>
</div>

<?php include "footer_site.php"; ?>

<!-- OpenSeadragon library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/openseadragon/4.0.0/openseadragon.min.js"></script>
<script type="text/javascript">
    var viewer = OpenSeadragon({
        id: "openseadragon1",
        prefixUrl: "https://cdnjs.cloudflare.com/ajax/libs/openseadragon/4.0.0/images/",
        tileSources: {
            type: 'image',
            url: 'imgs/<?php echo Security\h($show_product); ?>',
            buildPyramid: false
        },
        gestureSettingsMouse: { clickToZoom: true },
        showNavigationControl: true,
        showNavigator: true,
        navigatorPosition: "BOTTOM_RIGHT",
        zoomInButton: "zoom-in",
        zoomOutButton: "zoom-out",
        homeButton: "home",
        fullPageButton: "full-page"
    });
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "<?php echo Security\h($proname); ?>",
  "image": "<?php echo SITE_URL; ?>/home/imgs/<?php echo Security\h($show_product); ?>",
  "description": "<?php echo Security\h($pageDesc); ?>",
  "brand": { "@type": "Brand", "name": "<?php echo SITE_NAME; ?>" }
}
</script>
</body>
</html>