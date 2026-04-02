<?php
require_once "../includes/connectdb.php";

// Xóa trắng dữ liệu session
$_SESSION = array();

// Nếu muốn xóa sạch cookie session trên trình duyệt
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Phá hủy session
session_destroy();

// Điều hướng về trang login
header("location: ../admin");
exit;
?>