<?php
require_once "../includes/connectdb.php";

// Session và Security đã được nạp ở connectdb.php

if (isset($_SESSION['id'])) { 
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // 1. KIỂM TRA CSRF TOKEN
        if (!isset($_POST['csrf_token']) || !Security\verify_csrf_token($_POST['csrf_token'])) {
            die("Lỗi bảo mật: CSRF Token không hợp lệ!");
        }

        $oldpass = $_POST["passold"];
        $newpass = $_POST["passnew"];
        
        // 2. MÃ NGUỒN MỚI: SỬ DỤNG PREPARED STATEMENTS
        $stmt_check = $link->prepare("SELECT password FROM admin WHERE username = ?");
        $stmt_check->bind_param("s", $_SESSION["id"]);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        $row = $res_check->fetch_assoc();

        if($row && password_verify($oldpass, $row["password"])) {
            $hashed_password = password_hash($newpass, PASSWORD_DEFAULT);
            
            $stmt_up = $link->prepare("UPDATE admin SET password = ? WHERE username = ?");
            $stmt_up->bind_param("ss", $hashed_password, $_SESSION["id"]);
            
            if ($stmt_up->execute()) {
                echo '<script language="javascript">alert("Success!!"); window.location.href = "../admin";</script>';
            } else {
                echo '<script language="javascript">alert("error updating password!"); window.location.href = "../admin/changepass.php";</script>';
            }
            $stmt_up->close();
        } else {
            echo '<script language="javascript">alert("Password is wrong!!"); window.location.href = "../admin/changepass.php";</script>';
        }
        $stmt_check->close();
    }
} else {
    echo '<script language="javascript">alert("Website not available.\nYou will return Login, right now."); window.location.href = "../admin/";</script>';
}
$link->close();
?>