<?php
session_start();
require_once "../../includes/connectdb.php";

if (!isset($_SESSION["id"])) {
    header("location: ../login.php");
    exit;
}

// Categories for Sidebar
$show_protype = "";
$sql_types = "SELECT protype, COUNT(*) as count FROM products GROUP BY protype ORDER BY protype ASC";
$result_types = $link->query($sql_types);
$total_types = 0;
if ($result_types) {
    $total_types = $result_types->num_rows;
    while ($type_row = $result_types->fetch_assoc()) {
        $show_protype .= '
        <li class="sidebar-category-item">
            <a href="../../admin/protype.php?id=' . urlencode($type_row["protype"]) . '">
                <span>' . htmlspecialchars($type_row["protype"]) . ' (' . $type_row["count"] . ')</span>
            </a>
            <button type="button" class="btn btn-link btn-sm text-danger btndelprotype" value="' . htmlspecialchars($type_row["protype"]) . '" title="Xóa danh mục">
                <i class="fa fa-trash"></i>
            </button>
        </li>';
    }
}

// Get total count for stats
$total_res = $link->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc();
$total_records = $total_res['total'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm sản phẩm mới | Mẫu CNC Admin</title>
    <link rel="shortcut icon" href="../../home/imgs/logo/mt_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="../../home/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
    <script src="../../home/js/jquery.js"></script>
    <script src="../../home/js/bootstrap.min.js"></script>
    <style>
        .upload-zone {
            background: #fff;
            border: 2px dashed #ddd;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s;
            margin-bottom: 30px;
            cursor: pointer;
        }
        .upload-zone:hover {
            border-color: var(--accent-blue);
            background: rgba(52, 152, 219, 0.05);
        }
        .compressedImagesList {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 20px;
            list-style: none;
            padding: 0;
            margin-top: 30px;
        }
        .compressedImagesList li {
            background: #fff;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: relative;
            text-align: center;
        }
        .compressedImagesList li img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .compressedImagesList li p {
            font-size: 11px;
            margin: 0;
            color: #95a5a6;
        }
        .remove-button {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #e74c3c;
            color: #fff;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #uploadButton {
            width: 100%;
            padding: 15px;
            font-weight: 700;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .category-list li a { display: flex; align-items: center; }
        .btndelprotype:hover { color: #ff7675 !important; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <img src="../../home/imgs/logo/mt_logo.png" alt="Logo" style="height: 40px; margin-bottom: 10px;">
            <h2>MẪU CNC</h2>
            <p style="font-size: 12px; color: #95a5a6; margin: 0;">Admin Portal</p>
        </div>
        <div class="sidebar-nav">
            <div class="nav-group-title">Menu Chính</div>
            <ul>
                <li><a href="../../admin/admin.php"><i class="fa fa-home mr-2"></i> <span>Tổng quan</span></a></li>
                <li><a href="index.php" class="active"><i class="fa fa-plus-circle mr-2"></i> <span>Thêm sản phẩm</span></a></li>
                <li><a href="../../admin/changepass.php"><i class="fa fa-key mr-2"></i> <span>Đổi mật khẩu</span></a></li>
                <li><a href="../../admin/logout.php"><i class="fa fa-sign-out mr-2"></i> <span>Đăng xuất</span></a></li>
            </ul>

            <div class="nav-group-title mt-4">Danh mục sản phẩm</div>
            <ul class="category-list">
                <?php echo $show_protype; ?>
            </ul>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <div>
                <h1 style="font-weight: 700; font-size: 28px; margin: 0;">Thêm mẫu mới</h1>
                <p class="text-muted">Tải ảnh lên và hệ thống sẽ tự động nén tối ưu</p>
            </div>
            
            <form action="../../admin/admin.php" method="GET" class="search-wrapper">
                <i class="fa fa-search"></i>
                <input type="text" name="search" placeholder="Tìm kiếm nhanh...">
            </form>
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
                        <input name="nameimg" type="text" class="form-control" placeholder="Nhập tên mẫu (ví dụ: Tranh phật giáo)">
                        <small class="text-muted">Số thứ tự sẽ tự động được thêm nếu tải nhiều ảnh.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label>Loại danh mục</label>
                        <input name="typeimg" type="text" class="form-control" list="categoryList" placeholder="Nhập loại (nếu trùng loại cũ sẽ tự gộp)">
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
                        <textarea name="desimg" class="form-control" rows="3" placeholder="Thông tin chi tiết về mẫu..."></textarea>
                    </div>

                    <button id="uploadButton" class="btn btn-primary btn-lg" disabled>
                        <i class="fa fa-upload mr-2"></i> HÃY CHỌN ẢNH ĐỂ BẮT ĐẦU
                    </button>
                </div>

                <!-- Preview Grid -->
                <ul class="compressedImagesList" id="compressedImagesList"></ul>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 p-4" style="border-radius: 15px; background: var(--primary-slate); color: #fff;">
                    <h5 style="font-weight: 700;"><i class="fa fa-info-circle mr-2"></i> Hướng dẫn</h5>
                    <ul class="mt-3" style="font-size: 13px; line-height: 1.8; padding-left: 15px;">
                        <li>Bạn có thể chọn <b>nhiều ảnh</b> cùng lúc.</li>
                        <li>Hệ thống sẽ tự động nén ảnh xuống dưới <b>500KB</b> để web tải nhanh hơn.</li>
                        <li>Tên mẫu sẽ tự động đánh số (ví dụ: Tranh 1, Tranh 2...) nếu bạn tải lên nhiều tấm một lượt.</li>
                        <li>Nếu bạn sửa dụng lại tên <b>loại danh mục</b> đã có, sản phẩm sẽ được thêm vào thư mục đó.</li>
                    </ul>
                </div>

                <div class="stat-card mt-4">
                    <div class="stat-icon"><i class="fa fa-bar-chart"></i></div>
                    <div>
                        <div style="font-size: 20px; font-weight: 700;"><?php echo $total_records; ?></div>
                        <div style="font-size: 12px; color: #95a5a6;">Sản phẩm hiện có</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="lossy-compression.js?v=<?php echo time(); ?>"></script>
    <script src="../../home/js/my.js"></script>
</body>
</html>