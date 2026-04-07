<?php
require_once "../includes/connectdb.php";
require_once "../includes/security.php";

// Đảm bảo MT_CNC_AUTH được định nghĩa để nạp security.php
if (!defined('MT_CNC_AUTH')) define('MT_CNC_AUTH', true);

/**
 * CÔNG CỤ DỌN DẸP ẢNH RÁC (Developer Only)
 * ──────────────────────────────────────
 * Chế độ 1: Liệt kê (Mặc định) -> Thêm ?key=MẬT_MÃ_CỦA_BẠN
 * Chế độ 2: Xóa thực sự -> Thêm ?key=MẬT_MÃ_CỦA_BẠN&confirm=true
 */

// 1. KIỂM TRA CHÌA KHÓA DEV (Từ file .env)
$dev_key = getenv('DEV_CLEANUP_KEY') ?: '';
$provided_key = $_GET['key'] ?? '';

// Ghi nhật ký truy cập (Audit Log)
$log_file = __DIR__ . '/logs/security.log';
$timestamp = date("Y-m-d H:i:s");
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$log_msg = "[$timestamp] [CLEANUP] Access Attempt: IP=$ip, Key=" . ($provided_key === $dev_key ? 'MATCH' : 'DENIED') . ", Confirm=" . ($_GET['confirm'] ?? 'false') . "\n";
@file_put_contents($log_file, $log_msg, FILE_APPEND);

// Chặn nếu sai mã hoặc chưa cấu hình mã
if (empty($dev_key) || $provided_key !== $dev_key) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(["status" => "error", "message" => "Access Denied. Secret Key required."], JSON_PRETTY_PRINT);
    exit;
}

if (!isset($_SESSION['id'])) {
    die("Unauthorized (Session expired)");
}

$upload_dir = "../home/imgs/";
$system_files = ['.htaccess', 'logo', 'logo_watermark.png', '.', '..'];
$confirm_delete = (isset($_GET['confirm']) && $_GET['confirm'] === 'true');

// 1. Lấy danh sách ảnh đang dùng trong Database
$db_images = [];
$sql = "SELECT prourl FROM products";
$result = $link->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $db_images[] = $row['prourl'];
    }
}

// 2. Quét thư mục ảnh vật lý
$dir_files = scandir($upload_dir);
$deleted_count = 0;
$kept_count = 0;
$orphans = [];

foreach ($dir_files as $file) {
    // Bỏ qua các file hệ thống quan trọng
    if (in_array($file, $system_files) || is_dir($upload_dir . $file)) {
        continue;
    }

    // Nếu ảnh KHÔNG nằm trong DB -> Là ảnh mồ côi (ảnh rác)
    if (!in_array($file, $db_images)) {
        $orphans[] = $file;
        if ($confirm_delete) {
            if (@unlink($upload_dir . $file)) {
                $deleted_count++;
            }
        }
    } else {
        $kept_count++;
    }
}

// Trả về kết quả
$response = [
    "status" => "success",
    "mode" => $confirm_delete ? "DELETION_MODE" : "DRY_RUN_MODE",
    "summary" => [
        "total_files_scanned" => count($dir_files),
        "active_images_kept" => $kept_count,
        "orphans_found" => count($orphans),
        "orphans_deleted" => $deleted_count
    ],
    "orphans_list" => $orphans
];

if (!$confirm_delete && count($orphans) > 0) {
    $response["instruction"] = "Để thực hiện xóa thực sự, hãy thêm tham số '&confirm=true' vào URL.";
}

header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
