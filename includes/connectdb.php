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
if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'production');
}

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

// Nạp thư viện bảo mật (Sử dụng đường dẫn tuyệt đối)
require_once __DIR__ . "/security.php";

// -------------------------------------------------------------
// CẤU HÌNH GOOGLE RECAPTCHA (CENTRALIZED CONFIG)
// -------------------------------------------------------------
// Việc tập trung các khóa nhạy cảm vào một file giúp:
// 1. Dễ dàng thay đổi khi deploy lên các môi trường khác nhau.
// 2. Tránh rò rỉ khóa khi push code lên Git (nếu kết hợp .env).
// -------------------------------------------------------------
// GIẢI THÍCH BẢO MẬT: TÁCH BIỆT CODE VÀ CẤU HÌNH
// -------------------------------------------------------------
// Tuyệt đối KHÔNG ghi cứng (Hardcode) Key thật tại đây khi up lên GitHub.
// Thay vào đó, bạn hãy điền Key thật vào file .env (file này đã được chặn trong .gitignore).
// -------------------------------------------------------------
define('RECAPTCHA_SITE_KEY', getenv('RECAPTCHA_SITE_KEY') ?: '');
define('RECAPTCHA_SECRET_KEY', getenv('RECAPTCHA_SECRET_KEY') ?: '');
define('RECAPTCHA_ENABLED', getenv('RECAPTCHA_ENABLED') ?: 'true');

// -------------------------------------------------------------
// CẤU HÌNH THÔNG BÁO TELEGRAM (ADMIN NOTIFICATIONS)
// -------------------------------------------------------------
define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: '');
define('TELEGRAM_CHAT_ID', getenv('TELEGRAM_CHAT_ID') ?: '');
// -------------------------------------------------------------
// -------------------------------------------------------------

// function get_product($userid,$link){
//     $row_fr3 = array();
//     $sql_fr1="SELECT user2 as id,friend_stt,create_time FROM relationships 
//          WHERE user1 = '  $userid  'and friend_stt=1;"; 
         
//     $result_fr1 = $link->query($sql_fr1);
//     if ($result_fr1->num_rows > 0){
//         while($row_fr1 = mysqli_fetch_assoc($result_fr1)){
//             $rf=$row_fr1["id"];
//             $fr1="SELECT * FROM users
//                 WHERE id = '  $rf  ';"; 
//             $rs=$link->query($fr1);
//             $r_fr1 = mysqli_fetch_assoc($rs);
//             array_push($row_fr3,$r_fr1["id"]);
//             //dien vo day
//         }
//     }
// }
