<?php
require_once "../includes/connectdb.php";

if (isset($_SESSION['id'])) {
    
    // 1. KIỂM TRA CSRF TOKEN
    if (!isset($_GET['token']) || !Security\verify_csrf_token($_GET['token'])) {
        die("Lỗi bảo mật: CSRF Token không hợp lệ!");
    }

    $id = $_GET['t']; // protype name

    /*
    // ---------------------------------------------------------
    // MÃ NGUỒN CŨ (CỰC KỲ NGUY HIỂM - DỄ BỊ SQL INJECTION)
    // ---------------------------------------------------------
    // Lỗi: Biến $id được lấy trực tiếp từ $_GET và nối chuỗi vào câu lệnh SQL.
    // Kẻ tấn công có thể chèn các đoạn mã SQL độc hại qua tham số 't'.
    
    $sql_fr1 = 'SELECT * FROM products WHERE protype = "' . $id . '"';
    $result_fr1 = $link->query($sql_fr1);

    if ($result_fr1 && $result_fr1->num_rows > 0) {
        while ($row_fr1 = mysqli_fetch_assoc($result_fr1)) {
            $imageUrl = '../home/imgs/' . $row_fr1["prourl"];
            if (file_exists($imageUrl)) {
                unlink($imageUrl);
            }
        }
    }

    $sql = 'DELETE FROM products WHERE protype = "' . $id . '"';
    $result = $link->query($sql);
    if ($result) { echo "deleted"; } else { echo "error"; }
    // ---------------------------------------------------------
    */

    // MÃ NGUỒN MỚI: SỬ DỤNG PREPARED STATEMENTS (AN TOÀN)
    $stmt_select = $link->prepare("SELECT prourl FROM products WHERE protype = ?");
    $stmt_select->bind_param("s", $id);
    $stmt_select->execute();
    $result_fr1 = $stmt_select->get_result();

    if ($result_fr1 && $result_fr1->num_rows > 0) {
        while ($row_fr1 = $result_fr1->fetch_assoc()) {
            // -------------------------------------------------------------
            // GIẢI THÍCH BẢO MẬT: ĐƯỜNG DẪN TUYỆT ĐỐI (ABSOLUTE PATHS)
            // -------------------------------------------------------------
            // Chống lỗi không tìm thấy file khi deploy trên Hosting.
            $basePath = realpath(__DIR__ . '/../home/imgs/') . DIRECTORY_SEPARATOR;
            $clean_file = basename($row_fr1["prourl"]);
            $imageUrl = $basePath . $clean_file;
            // -------------------------------------------------------------

            if (file_exists($imageUrl)) {
                unlink($imageUrl);
            }
        }
    }
    $stmt_select->close();

    // Thực hiện xóa bản ghi trong DB
    $stmt_del = $link->prepare("DELETE FROM products WHERE protype = ?");
    $stmt_del->bind_param("s", $id);
    
    if ($stmt_del->execute()) {
        echo "deleted";
    } else {
        echo "error";
    }
    $stmt_del->close();

} else {
    echo "";
}
?>
