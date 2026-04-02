<?php
/**
 * TRANG ĐĂNG XUẤT (ROOT)
 * ──────────────────────────────────────────
 * Mục tiêu: Kết thúc phiên làm việc một cách an toàn nhất.
 */

// 1. Nạp cơ chế bảo mật (Gia cố Session)
require_once "includes/connectdb.php";

// 2. Xóa các biến phiên làm việc
$_SESSION = array();

// 3. Xóa Cookie Session bám trên trình duyệt (Harden Logout)
// Giải thích: Phá hủy session trên server là chưa đủ, ta cần báo cho 
// trình duyệt xóa bỏ Cookie session ID để triệt tiêu hoàn toàn dấu vết.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Phá hủy Session trên server
session_destroy();

// 5. Điều hướng về trang login (Đường dẫn tương đối trực tiếp giúp hỗ trợ cài đặt trên Hosting/Thư mục con)
header("location: admin/login.php");
exit;
?>