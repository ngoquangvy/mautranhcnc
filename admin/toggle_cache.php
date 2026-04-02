<?php
require_once "../includes/connectdb.php";
require_once "../includes/cache.php";

if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // KIỂM TRA CSRF TOKEN
    if (!isset($_POST['csrf_token']) || !Security\verify_csrf_token($_POST['csrf_token'])) {
        die("Lỗi bảo mật: CSRF Token không hợp lệ!");
    }
    FileCache::toggle();
}

header("Location: admin.php");
exit;
