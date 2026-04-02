<?php
require_once "../includes/connectdb.php";
require_once "../includes/security.php";

$id = $_GET['id'];
$show_product = "";
$proname = "Chi tiết mẫu";
$protype = "";

/*
// MÃ NGUỒN CŨ (DỄ BỊ SQL INJECTION)
$sql_fr1 = 'SELECT * from products where id ="' . $id . '"  ';
$result_fr1 = $link->query($sql_fr1);
*/

// MÃ NGUỒN MỚI: SỬ DỤNG PREPARED STATEMENTS
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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($proname); ?> | Mẫu CNC</title>
    <link rel="shortcut icon" href="imgs/logo/mt_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        :root {
            --primary-slate: #1e293b;
            --accent-emerald: #10b981;
            --glass-bg: rgba(15, 23, 42, 0.8);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            margin: 0;
            overflow: hidden;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Premium Header */
        .premium-header {
            position: fixed;
            top: 0; width: 100%;
            z-index: 1000;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 15px 0;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-back {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            transition: all 0.3s;
            background: rgba(255,255,255,0.1);
            padding: 8px 18px;
            border-radius: 50px;
        }
        .btn-back:hover { background: rgba(255,255,255,0.2); color: var(--accent-emerald); }

        .product-meta h1 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px;
        }
        .product-meta span { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }

        /* Viewer Area */
        main { flex: 1; position: relative; }
        #openseadragon1 { width: 100%; height: 100%; background-color: #0f172a; }

        /* Prevent image overlap on Desktop */
        @media (min-width: 768px) {
            #openseadragon1 { 
                height: calc(100vh - 170px); 
                margin-top: 85px; 
            }
        }

        /* Floating Contact Controls */
        .viewer-controls {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 10px 25px;
            border-radius: 100px;
            display: flex;
            align-items: center;
            gap: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        .control-btn {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: 0.3s;
        }
        .control-btn:hover { color: var(--accent-emerald); }
        .control-btn.zalo { color: #0084ff; }
        .control-btn.zalo:hover { color: white; }

        .divider { width: 1px; height: 20px; background: rgba(255,255,255,0.2); }

        /* Customization for OpenSeadragon Buttons */
        .openseadragon-container { font-family: 'Inter', sans-serif !important; }
    </style>
</head>
<body>

    <header class="premium-header">
        <div class="container header-content">
            <a href="javascript:history.back()" class="btn-back">
                <i class="fa fa-arrow-left"></i> <span>Quay lại</span>
            </a>
            
            <div class="product-meta text-center d-none d-md-block">
                <span><?php echo htmlspecialchars($protype); ?></span>
                <h1><?php echo htmlspecialchars($proname); ?></h1>
            </div>

            <a href="./" class="btn-back d-none d-sm-flex">
                <i class="fa fa-home"></i> <span>Trang chủ</span>
            </a>
        </div>
    </header>

    <main>
        <div id="openseadragon1"></div>
        
        <div class="viewer-controls">
            <a href="https://zalo.me/0338790560" class="control-btn zalo" target="_blank">
                <i class="fa fa-comments"></i> ZALO ĐẶT HÀNG
            </a>
            <div class="divider"></div>
            <a href="https://www.facebook.com/thien.bui.12327608" class="control-btn" target="_blank">
                <i class="fa fa-facebook-square"></i> FACEBOOK
            </a>
            <div class="divider"></div>
            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">HOTLINE: 0338.790.560</span>
        </div>
    </main>

    <script src="https://openseadragon.github.io/openseadragon/openseadragon.min.js"></script>
    <script>
        const viewer = OpenSeadragon({
            id: 'openseadragon1',
            prefixUrl: 'https://openseadragon.github.io/openseadragon/images/',
            tileSources: {
                type: 'image',
                url: 'imgs/<?php echo $show_product ?>'
            },
            showRotationControl: true,
            gestureSettingsMouse: { clickToZoom: true },
            animationTime: 0.5,
            blendingTime: 0.1,
            constrainDuringPan: true,
            maxZoomLevel: 10,
            minZoomLevel: 0.5,
            visibilityRatio: 1,
            zoomPerClick: 2
        });
    </script>
</body>
</html>