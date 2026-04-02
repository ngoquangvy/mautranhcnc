require_once "../includes/connectdb.php";

if (!isset($_SESSION['id'])) {
    die("Unauthorized");
}

// -------------------------------------------------------------
// GIẢI THÍCH BẢO MẬT: PHÒNG THỦ CHIỀU SÂU
// -------------------------------------------------------------
/*
// MÃ LỖI (GIẢ ĐỊNH) - CỰC KỲ NGUY HIỂM:
// 1. Không check SESSION: Bất kỳ ai biết URL cũng có thể kích hoạt dọn dẹp file.
// 2. Không check CSRF: Admin đang đăng nhập có thể bị lừa bấm vào link ẩn 
//    và xóa sạch kho ảnh của shop.
// 3. Thiếu sanitize filename: Scanner có thể bị lừa xóa cả file hệ thống khác.

$dir_files = scandir("../home/imgs/");
foreach ($dir_files as $f) {
   if (!in_array($f, $db_imgs)) unlink("../home/imgs/" . $f);
}
*/
// -------------------------------------------------------------

$upload_dir = "../home/imgs/";
$system_files = ['.htaccess', 'logo', 'logo_watermark.png', '.', '..'];

// 1. Get all images from Database
$db_images = [];
$sql = "SELECT prourl FROM products";
$result = $link->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $db_images[] = $row['prourl'];
    }
}

// 2. Scan physical directory
$dir_files = scandir($upload_dir);
$deleted_count = 0;
$kept_count = 0;
$orphans = [];

foreach ($dir_files as $file) {
    // Skip system files and directories
    if (in_array($file, $system_files) || is_dir($upload_dir . $file)) {
        continue;
    }

    // Check if file exists in DB
    if (!in_array($file, $db_images)) {
        $orphans[] = $file;
        if (unlink($upload_dir . $file)) {
            $deleted_count++;
        }
    } else {
        $kept_count++;
    }
}

echo json_encode([
    "status" => "success",
    "total_checked" => count($dir_files),
    "deleted_orphans" => $deleted_count,
    "kept_active" => $kept_count,
    "orphaned_list" => $orphans
], JSON_PRETTY_PRINT);
