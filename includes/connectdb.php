<?php
/* 
 * 1. TỰ ĐỘNG NẠP CẤU HÌNH .ENV (DÙNG CHO SERVER TRUYỀN THỐNG / XAMPP)
 * ────────────────────────────────────────────────────────────────
 * Đoạn mã này giúp bạn cấu hình DB mà không cần sửa code. 
 * Chỉ cần tạo file .env ở thư mục gốc của project.
 */
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Xóa dấu ngoặc kép nếu có bao quanh giá trị
            $value = trim($value, '"\'');

            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

/* 2. CẤU HÌNH MÔI TRƯỜNG: local | production */
/* MÔI TRƯỜNG & PHIÊN BẢN (Quản soát SESSION) */
define('APP_ENV', isset($_ENV['APP_ENV']) ? $_ENV['APP_ENV'] : 'production');
define('SESSION_VERSION', isset($_ENV['SESSION_VERSION']) ? $_ENV['SESSION_VERSION'] : '1.0.0');

/* 3. BẢO MẬT: TẮT HIỂN THỊ LỖ TRÊN PRODUCTION */
if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

/* 4. DB Credentials: Ưu tiên lấy từ biến môi trường (Docker/.env) */
define('DB_SERVER', getenv('DB_HOST') ?: 'localhost');
define('DB_USERNAME', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'ngovy_maucnc');

/* Khởi tạo kết nối MySQL */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

$link->set_charset("utf8mb4");

// ĐỒNG BỘ MÚI GIỜ SQL VỚI VIỆT NAM (+07:00)
$link->query("SET time_zone = '+07:00'");

// Nạp thư viện bảo mật (Sử dụng đường dẫn tuyệt đối)
require_once __DIR__ . "/security.php";

// -------------------------------------------------------------
// CẤU HÌNH GOOGLE RECAPTCHA (CENTRALIZED CONFIG)
// -------------------------------------------------------------
define('RECAPTCHA_SITE_KEY', getenv('RECAPTCHA_SITE_KEY') ?: '');
define('RECAPTCHA_SECRET_KEY', getenv('RECAPTCHA_SECRET_KEY') ?: '');
define('RECAPTCHA_ENABLED', getenv('RECAPTCHA_ENABLED') ?: 'true');

// -------------------------------------------------------------
// CẤU HÌNH THÔNG BÁO EMAIL (ADMIN NOTIFICATIONS)
// -------------------------------------------------------------
define('ADMIN_CAN_DELETE_ORDER', strtolower(getenv('ADMIN_CAN_DELETE_ORDER') ?: 'false') === 'true');

// SENDER_EMAIL: Email theo tên miền của bạn (ví dụ: admin@mautranhcnc.com)
// ADMIN_EMAIL: Email cá nhân nhận thông báo (ví dụ: abc@gmail.com)
// -------------------------------------------------------------
define('SENDER_EMAIL', getenv('SENDER_EMAIL') ?: 'bot@yourdomain.com');
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@yourdomain.com');
// -------------------------------------------------------------

?>
