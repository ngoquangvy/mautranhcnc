<?php
require_once "../../includes/connectdb.php";
require_once "../../includes/config_site.php";
if (!defined('SITE_LOGO_PREFIX'))
    define('SITE_LOGO_PREFIX', '../../home/');

if (!isset($_SESSION["id"])) {
    header("location: ../login.php");
    exit;
}

// Sidebar logic flag for include
$is_nested_admin = true;

// Get total count for stats and datalist
$total_res = $link->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc();
$total_records = $total_res['total'];

// Fetch categories for the datalist
$sql_types = "SELECT protype FROM products GROUP BY protype ORDER BY protype ASC";
$result_types = $link->query($sql_types);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm sản phẩm mới | <?php echo SITE_NAME; ?> Admin</title>
    <link rel="shortcut icon" href="<?php echo SITE_LOGO_PREFIX . SITE_LOGO; ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="../../home/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/admin.css?v=<?php echo time(); ?>" rel="stylesheet">
    <script src="../../home/js/jquery.js"></script>
    <script src="../../home/js/bootstrap.min.js"></script>
</head>

<body>

    <?php include "../sidebar_admin.php"; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <div>
                <h1 style="font-weight: 800; font-size: 32px; letter-spacing: -1.5px; margin: 0;"><?php echo mb_strtoupper(SITE_NAME, 'UTF-8'); ?></h1>
                <p class="text-muted" style="font-weight: 500;">Thêm mẫu mới & Hệ thống nén ảnh tự động</p>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-8">
                <div class="upload-zone" onclick="document.getElementById('imageUpload').click()">
                    <i class="fa fa-cloud-upload fa-3x mb-3" style="color: var(--accent-blue)"></i>
                    <h4>Click để chọn ảnh hoặc kéo thả vào đây</h4>
                    <p class="text-muted">Hỗ trợ định dạng JPG, JPEG, PNG...</p>
                    <input type="file" id="imageUpload" name="images" accept="image/*" multiple style="display: none;">
                </div>

                <div class="card shadow-sm border-0 p-4" style="border-radius: 15px;">
                    <h5 class="mb-4" style="font-weight: 700;">Thông tin mẫu</h5>

                    <div class="form-group mb-3">
                        <label>Tên mẫu</label>
                        <input name="nameimg" type="text" class="form-control"
                            placeholder="Nhập tên mẫu (ví dụ: Tranh phật giáo)">
                        <small class="text-muted">Số thứ tự sẽ tự động được thêm nếu tải nhiều ảnh.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label>Loại danh mục</label>
                        <input name="typeimg" type="text" class="form-control" list="categoryList"
                            placeholder="Nhập loại (nếu trùng loại cũ sẽ tự gộp)">
                        <datalist id="categoryList">
                            <?php
                            $result_types->data_seek(0); // Reset result pointer
                            while ($dl_row = $result_types->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($dl_row["protype"]) . '">';
                            }
                            ?>
                        </datalist>
                    </div>

                    <div class="form-group mb-4">
                        <label>Ghi chú (Tùy chọn)</label>
                        <textarea name="desimg" class="form-control" rows="3"
                            placeholder="Thông tin chi tiết về mẫu..."></textarea>
                    </div>

                    <button id="uploadButton" class="btn btn-primary btn-lg" disabled>
                        <i class="fa fa-upload mr-2"></i> HÃY CHỌN ẢNH ĐỂ BẮT ĐẦU
                    </button>
                </div>

                <!-- Preview Grid -->
                <ul class="compressedImagesList" id="compressedImagesList"></ul>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 p-4"
                    style="border-radius: 15px; background: var(--primary-slate); color: #fff;">
                    <h5 style="font-weight: 700;"><i class="fa fa-info-circle mr-2"></i> Hướng dẫn</h5>
                    <ul class="mt-3" style="font-size: 13px; line-height: 1.8; padding-left: 15px;">
                        <li>Bạn có thể chọn <b>nhiều ảnh</b> cùng lúc.</li>
                        <li>Hệ thống sẽ tự động nén ảnh xuống dưới <b>500KB</b> để web tải nhanh hơn.</li>
                        <li>Tên mẫu sẽ tự động đánh số (ví dụ: Tranh 1, Tranh 2...) nếu bạn tải lên nhiều tấm một lượt.
                        </li>
                        <li>Nếu bạn sửa dụng lại tên <b>loại danh mục</b> đã có, sản phẩm sẽ được thêm vào thư mục đó.
                        </li>
                    </ul>
                </div>

                <div class="stat-card mt-4">
                    <div class="stat-icon"><i class="fa fa-cubes"></i></div>
                    <div>
                        <div class="stat-count"><?php echo $total_records; ?></div>
                        <div class="stat-title">Sản phẩm hiện có</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // TRUYỀN CSRF TOKEN TỪ PHP SANG JAVASCRIPT
        window.CSRF_TOKEN = "<?php echo Security\generate_csrf_token(); ?>";
    </script>
    <script src="lossy-compression.js?v=<?php echo time(); ?>"></script>
    <script src="../../home/js/my.js"></script>
</body>

</html>