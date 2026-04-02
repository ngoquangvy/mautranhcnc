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
    $token = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
    $chat_id = defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '';

    if (empty($token) || empty($chat_id)) {
        return false; // Chưa cấu hình thì bỏ qua
    }

    $url = "https://api.telegram.org/bot$token/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    // Gửi request bằng file_get_contents với timeout ngắn
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data),
            'timeout' => 10 // 10 giây
        ],
        // ─────────────────────────────────────────────────────────────
        // BẢO MẬT SSL (Chống tấn công Man-in-the-Middle)
        // ─────────────────────────────────────────────────────────────
        // Mặc định luôn bật xác thực để bảo vệ mã Token bí mật.
        'ssl' => [
            'verify_peer' => getenv('SSL_VERIFY') !== 'false',
            'verify_peer_name' => getenv('SSL_VERIFY') !== 'false'
        ]
    ];

    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);
    // Dùng @ để ẩn lỗi nếu server không có mạng, tránh làm hỏng luồng đặt hàng.
    return true;
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

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data),
            'timeout' => 5
        ],
        'ssl' => [
            'verify_peer' => getenv('SSL_VERIFY') !== 'false',
            'verify_peer_name' => getenv('SSL_VERIFY') !== 'false'
        ]
    ];

    $context = stream_context_create($options);
    $verify_response = @file_get_contents($verify_url, false, $context);

    if ($verify_response === false)
        return false;

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
            "img-src 'self' data: https://openseadragon.github.io https://www.gstatic.com; " .
            "frame-src 'self' https://www.google.com;");
        // -------------------------------------------------------------
    }
}

// KHỞI CHẠY SESSION AN TOÀN NGAY LẬP TỨC
secure_session_start();
?>
