<?php
require_once "../includes/connectdb.php";

if (isset($_SESSION['id'])) {

    // 1. KIỂM TRA CSRF TOKEN (Bảo vệ khỏi tấn công giả mạo)
    if (!isset($_GET['token']) || !Security\verify_csrf_token($_GET['token'])) {
        die("Lỗi bảo mật: CSRF Token không hợp lệ!");
    }

    // 2. LẤY VÀ LÀM SẠCH TÊN FILE (Chống Path Traversal)
    $id = $_GET['t'];
    $clean_filename = Security\sanitize_filename($id);

    /*
    // ---------------------------------------------------------
    // MÃ NGUỒN CŨ (NGUY CƠ SQL INJECTION & PATH TRAVERSAL)
    // ---------------------------------------------------------
    // 1. SQL Injection: Biến $id được nối thẳng vào chuỗi truy vấn.
    // 2. Path Traversal: Nếu $id là "../../index.php", lệnh unlink($imagePath) 
    //    có thể xóa nhầm các file hệ thống quan trọng.
    
    $sql = 'DELETE FROM products WHERE  prourl = "' . $id . '" ';
    $imagePath = "../home/imgs/" . $id;
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
    $result = $link->query($sql);
    // ---------------------------------------------------------
    */

    // 3. MÃ NGUỒN MỚI: SỬ DỤNG PREPARED STATEMENTS
    $stmt = $link->prepare("DELETE FROM products WHERE prourl = ?");
    $stmt->bind_param("s", $clean_filename);
    
    // -------------------------------------------------------------
    // GIẢI THÍCH BẢO MẬT: ĐƯỜNG DẪN TUYỆT ĐỐI KHI XÓA FILE (UNLINK)
    // -------------------------------------------------------------
    // Trên Hosting, đường dẫn tương đối (../) có thể không ổn định.
    // GIẢI PHÁP: Dùng __DIR__ để tạo đường dẫn vật lý chính xác 100%.
    $basePath = realpath(__DIR__ . '/../home/imgs/') . DIRECTORY_SEPARATOR;
    $imagePath = $basePath . $clean_filename;
    // -------------------------------------------------------------

    $deleted = false;
    if ($stmt->execute()) {
        $deleted = true;
        // 4. CHỈ XÓA FILE VẬT LÝ KHI DATABASE ĐÃ XÓA THÀNH CÔNG
        // Điều này đảm bảo không bao giờ xảy ra tình trạng "Ảnh vỡ" trên giao diện.
        if ($stmt->affected_rows > 0 && file_exists($imagePath)) {
            @unlink($imagePath);
        }
        echo 'deleted';
    } else {
        echo "error";
    }

    $stmt->close();
} else {
    echo "";
}
?>
