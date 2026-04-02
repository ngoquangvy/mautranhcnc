<?php
/**
 * Security Library
 * ──────────────────────────────────────────
 * Mục tiêu: Cung cấp các phòng thủ chuẩn hóa cho toàn bộ hệ thống.
 */

namespace Security;

/**
 * CSFR PROTECTION (Chống Giả mạo yêu cầu từ phía Web khác)
 * ──────────────────────────────────────────────────
 * Kịch bản lỗi: Nếu không có token này, Hacker có thể lừa Admin bấm vào 
 * một link "xoa_don_hang.php?id=10" từ một trang web khác để thực hiện 
 * hành động trái phép mà Admin không hề hay biết.
 */
function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        // Tạo chuỗi ngẫu nhiên không thể đoán trước
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token)
{
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    // Sử dụng hàm hash_equals để chống lại tấn công Timing Attack
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * HTML OUTPUT ESCAPING (Chống Cross-Site Scripting - XSS)
 * ───────────────────────────────────────────────────
 * Shorthand cho htmlspecialchars.
 * Nguy cơ: Nếu không dùng hàm này khi 'echo' dữ liệu từ DB ra ngoài, 
 * kẻ xấu có thể chèn mã JS như <script>alert(document.cookie)</script> 
 * để đánh cắp Session của Admin.
 */
function h($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * PATH SANITIZATION (Chống Path Traversal)
 * ──────────────────────────────────────
 * Nguy cơ: Nếu Hacker gửi tham số t=../../config.php vào hàm xóa file, 
 * hệ thống có thể bị lừa xóa nhầm các file cấu hình quan trọng.
 * basename() giúp trích xuất đúng tên file và loại bỏ các ký tự điều hướng thư mục.
 */
function sanitize_filename($filename)
{
    return basename($filename);
}

/**
 * INPUT VALIDATION HELPERS (Xác thực dữ liệu đầu vào)
 */
function validate_id($id)
{
    return filter_var($id, FILTER_VALIDATE_INT);
}

function is_recaptcha_enabled()
{
    return strtolower((string) RECAPTCHA_ENABLED) === 'true';
}

/**
 * ADMIN NOTIFICATION (Thông báo qua Telegram Bot)
 * ──────────────────────────────────────────
 * Mục tiêu: Thông báo ngay lập tức cho Admin khi có đơn hàng mới.
 * Cách lấy Token: Chat với @BotFather trân Telegram.
 */
function notify_admin($message)
{
    /* 
     * ─────────────────────────────────────────────────────────────────────────
     * LƯU Ý QUAN TRỌNG VỀ HOSTING MIỄN PHÍ (FREEHOSTIA / BYETHOST / ...)
     * ─────────────────────────────────────────────────────────────────────────
     * Các Host free thường chặn yêu cầu gửi ra ngoài (Outbound Requests) 
     * tới các dịch vụ như Telegram hoặc Google reCAPTCHA.
     * Do đó, chúng ta chuyển sang dùng hàm mail() nội bộ của Server.
     */

    // 1. [VÔ HIỆU HÓA TELEGRAM] - Tạm đóng do Host chặn kết nối ngoại vi.
    /*
    $token = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
    $chat_id = defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '';

    if (!empty($token) && !empty($chat_id)) {
        $url = "https://api.telegram.org/bot$token/sendMessage";
        $data = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
        // ... (phần code gửi bằng Curl hoặc file_get_contents)
    }
    */

    // 2. [TRIỂN KHAI EMAIL] - Sử dụng hàm mail() chuẩn PHP.
    $to = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : '';
    $from = defined('SENDER_EMAIL') ? SENDER_EMAIL : '';

    if (empty($to) || empty($from)) {
        return false; // Chưa cấu hình Email thì bỏ qua
    }

    $subject = "=?UTF-8?B?".base64_encode("Thông báo Đơn hàng mới: " . date("H:i"))."?=";
    
    // Header chuẩn để tránh rơi vào Spam và hỗ trợ Tiếng Việt
    $headers = "From: Mẫu Tranh CNC <" . $from . ">\r\n";
    $headers .= "Reply-To: " . $from . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Gửi thư (Dùng @ để ẩn lỗi của server nếu hàm mail bị tắt)
    $sent = @mail($to, $subject, $message, $headers);

    // -------------------------------------------------------------
    // [CODE MỚI - LOG LỖI GỬI MAIL]
    // -------------------------------------------------------------
    // Mục tiêu: Nếu gửi mail thất bại, ghi lại vào log để Admin biết.
    if (!$sent) {
        $log_file = __DIR__ . '/../admin/logs/notif_error.log';
        $timestamp = date("Y-m-d H:i:s");
        $log_msg = "[$timestamp] LỖI: Không thể gửi mail tới $to. Vui lòng kiểm tra lại cấu hình Hosting/Email.\n";
        // Ghi vào file log (Dùng FILE_APPEND để không xóa các lỗi cũ)
        @file_put_contents($log_file, $log_msg, FILE_APPEND);
    }
    // -------------------------------------------------------------

    return $sent;
}

/**
 * GOOGLE reCAPTCHA V2 VERIFICATION
 * ──────────────────────────────────────────
 * Mục tiêu: Ngăn chặn Bot tự động spam đơn hàng và Telegram.
 * Cơ chế: Gửi mã phản hồi (g-recaptcha-response) lên Google server để xác thực.
 */
function verify_recaptcha($response)
{
    if (!is_recaptcha_enabled()) {
        return true;
    }

    if (empty($response))
        return false;

    // Lấy Secret Key từ biến môi trường
    $secret = defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : '';
    // Fail-closed: Nếu thiếu Key ở Production, mặc định là CHẶN (False). 
    // Chỉ cho phép đi qua ở môi trường 'local' để thuận tiện phát triển.
    if (empty($secret))
        return (defined('APP_ENV') && APP_ENV === 'local');

    $verify_url = "https://www.google.com/recaptcha/api/siteverify";
    $data = [
        'secret' => $secret,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $ch = curl_init($verify_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, getenv('SSL_VERIFY') !== 'false');
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, getenv('SSL_VERIFY') !== 'false' ? 2 : 0);

    $verify_response = curl_exec($ch);

    if ($verify_response === false) {
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    $result = json_decode($verify_response, true);
    return (isset($result['success']) && $result['success'] == true);
}

/**
 * SESSION HARDENING (Thắt chặt an ninh Phiên làm việc)
 * ────────────────────────────────────────────────
 * 1. HttpOnly: Ngăn chặn Javascript truy cập Cookie (Chống XSS lấy Session ID).
 * 2. SameSite=Lax: Ngăn chặn gửi Cookie kèm theo các request từ domain khác (Chống CSRF mặc định).
 * 3. use_only_cookies: Buộc dùng Cookie, không cho phép Session ID xuất hiện trên URL.
 */
function secure_session_start()
{
    if (session_status() === PHP_SESSION_NONE) {
        $session_name = 'mtcnc_session';
        // -------------------------------------------------------------
        // GIẢI THÍCH BẢO MẬT: COOKIE SECURE FLAG
        // -------------------------------------------------------------
        // Cờ 'secure' = true báo cho trình duyệt CHỈ gửi cookie qua HTTPS.
        // Điều này ngăn chặn việc session bị đánh cắp khi admin dùng mạng 
        // công cộng không an toàn (Sniffing).

        $secure = (APP_ENV === 'production') || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        $httponly = true;
        $samesite = 'Lax';
        // -------------------------------------------------------------

        // Cấu hình an toàn cho Session trước khi start
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
        ]);

        session_start();
        
        // -------------------------------------------------------------
        // [CODE MỚI - QUẢN LÝ PHIÊN BẢN SESSION]
        // -------------------------------------------------------------
        // Mục tiêu: Nếu bạn đổi SESSION_VERSION trong .env, toàn bộ người dùng 
        // sẽ được reset session để tránh xung đột hoặc lỗi bảo mật cũ.
        if (!isset($_SESSION['SESSION_VERSION']) || $_SESSION['SESSION_VERSION'] !== SESSION_VERSION) {
            session_unset(); // Xóa sạch dữ liệu cũ
            $_SESSION['SESSION_VERSION'] = SESSION_VERSION; // Ghi nhận phiên bản mới
        }
        // -------------------------------------------------------------

        // -------------------------------------------------------------
        // GIẢI THÍCH BẢO MẬT: GLOBAL SECURITY HEADERS
        // -------------------------------------------------------------
        // 1. X-Frame-Options: Ngăn chặn trang web bị chèn vào iframe của 
        // trang khác, giúp chống tấn công Clickjacking (đánh lừa click).
        header("X-Frame-Options: SAMEORIGIN");

        // 2. X-Content-Type-Options: Ngăn trình duyệt tự ý đoán kiểu nội dung 
        // (MIME Sniffing), buộc phải tuân theo Content-Type của server.
        header("X-Content-Type-Options: nosniff");

        // 3. Referrer-Policy: Kiểm soát lượng thông tin được gửi đi trong 
        // header Referer khi người dùng chuyển hướng sang trang khác.
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // 4. Content-Security-Policy (CSP): Lớp bảo mật mạnh mẽ nhất 
        // giúp ngăn chặn XSS bằng cách quy định rõ nguồn nạp Script/Style.
        header("Content-Security-Policy: default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' https://openseadragon.github.io https://maxcdn.bootstrapcdn.com https://fonts.googleapis.com https://www.google.com https://www.gstatic.com; " .
            "style-src 'self' 'unsafe-inline' https://maxcdn.bootstrapcdn.com https://fonts.googleapis.com https://www.google.com; " .
            "font-src 'self' https://maxcdn.bootstrapcdn.com https://fonts.gstatic.com; " .
            "img-src 'self' data: https://openseadragon.github.io https://www.google.com https://www.gstatic.com; " .
            "frame-src 'self' https://www.google.com; " .
            "connect-src 'self' https://www.google.com https://www.gstatic.com;");

        // -------------------------------------------------------------
    }
}

// KHỞI CHẠY SESSION AN TOÀN NGAY LẬP TỨC
secure_session_start();
?>
