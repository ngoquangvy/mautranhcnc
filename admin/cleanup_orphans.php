<?php
require_once "../includes/connectdb.php";
require_once "../includes/security.php";

// Đảm bảo MT_CNC_AUTH được định nghĩa để nạp security.php
if (!defined('MT_CNC_AUTH')) define('MT_CNC_AUTH', true);

/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║        CÔNG CỤ DỌN DẸP ẢNH RÁC (Developer Only)          ║
 * ╠══════════════════════════════════════════════════════════════╣
 * ║                                                              ║
 * ║  MỤC ĐÍCH:                                                   ║
 * ║  Quét thư mục /home/imgs/ và so sánh với bảng `products`    ║
 * ║  trong Database. Nếu ảnh tồn tại trên ổ cứng nhưng KHÔNG   ║
 * ║  có bản ghi nào trong DB, thì đó là "ảnh mồ côi" (orphan). ║
 * ║                                                              ║
 * ║  NGUYÊN NHÂN SINH ẢNH MỒ CÔI:                              ║
 * ║  1. Admin xóa dữ liệu trực tiếp trong phpMyAdmin            ║
 * ║  2. Upload bị timeout giữa chừng (ảnh đã lưu, DB chưa ghi) ║
 * ║  3. Restore backup DB cũ nhưng ảnh mới vẫn còn trên server  ║
 * ║                                                              ║
 * ║  CÁCH SỬ DỤNG:                                               ║
 * ║  ─────────────────────────────────────────────────            ║
 * ║  Chế độ 1 — CHỈ XEM (An toàn, mặc định):                    ║
 * ║    URL: cleanup_orphans.php?key=<MẬT_MÃ_TRONG_.ENV>         ║
 * ║    → Trả về danh sách JSON các ảnh rác, KHÔNG xóa gì cả.   ║
 * ║                                                              ║
 * ║  Chế độ 2 — XÓA THỰC SỰ (Cần xác nhận):                    ║
 * ║    URL: cleanup_orphans.php?key=<MẬT_MÃ>&confirm=true       ║
 * ║    → Xóa vĩnh viễn các ảnh mồ côi khỏi server.             ║
 * ║                                                              ║
 * ║  BẢO MẬT:                                                    ║
 * ║  • Yêu cầu Session Admin hợp lệ (đã đăng nhập)             ║
 * ║  • Yêu cầu Chìa khóa Dev (DEV_CLEANUP_KEY trong .env)       ║
 * ║  • Mọi truy cập đều được ghi log tại admin/logs/security.log║
 * ║  • Các file hệ thống (.htaccess, logo...) LUÔN được bảo vệ  ║
 * ║                                                              ║
 * ╚══════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════
// BƯỚC 1: KIỂM TRA CHÌA KHÓA DEV (Từ file .env)
// ═══════════════════════════════════════════════════════════
// Đọc biến DEV_CLEANUP_KEY từ .env (được nạp bởi connectdb.php)
// Nếu URL không chứa ?key=... hoặc key sai → Trả 403 Forbidden
$dev_key = getenv('DEV_CLEANUP_KEY') ?: '';
$provided_key = $_GET['key'] ?? '';

// Ghi nhật ký truy cập (Audit Log) — Ghi CẢ thành công lẫn thất bại
$log_file = __DIR__ . '/logs/security.log';
$timestamp = date("Y-m-d H:i:s");
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$log_msg = "[$timestamp] [CLEANUP] Access Attempt: IP=$ip, Key=" . ($provided_key === $dev_key ? 'MATCH' : 'DENIED') . ", Confirm=" . ($_GET['confirm'] ?? 'false') . "\n";
@file_put_contents($log_file, $log_msg, FILE_APPEND);

// Chặn nếu sai mã hoặc chưa cấu hình mã trong .env
if (empty($dev_key) || $provided_key !== $dev_key) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(["status" => "error", "message" => "Access Denied. Secret Key required."], JSON_PRETTY_PRINT);
    exit;
}

// ═══════════════════════════════════════════════════════════
// BƯỚC 2: KIỂM TRA PHIÊN ADMIN
// ═══════════════════════════════════════════════════════════
if (!isset($_SESSION['id'])) {
    die("Unauthorized (Session expired)");
}

// ═══════════════════════════════════════════════════════════
// BƯỚC 3: CẤU HÌNH QUÉT
// ═══════════════════════════════════════════════════════════
$upload_dir = "../home/imgs/";
// Danh sách file TUYỆT ĐỐI KHÔNG ĐƯỢC XÓA (dù không có trong DB)
$system_files = ['.htaccess', 'logo', 'logo_watermark.png', '.', '..'];
// Chỉ xóa thực sự khi URL có tham số &confirm=true
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
