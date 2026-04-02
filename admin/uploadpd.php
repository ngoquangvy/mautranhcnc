<?php
require_once "../includes/connectdb.php";

// Set header to JSON for all responses
header('Content-Type: application/json');

/**
 * Return JSON error and exit
 */
function sendError($message)
{
    echo json_encode(["status" => "error", "message" => $message]);
    exit;
}

// Validate session
if (!isset($_SESSION["id"])) {
    sendError("Unauthorized access. Please log in.");
}

/**
 * Adds a watermark to an image file using a logo image and website URL.
 * Always saves as JPEG for consistency and efficiency.
 */
function addWatermark($target_file)
{
    if (!function_exists('imagecreatefromjpeg')) {
        return "GD library with JPEG support is missing";
    }

    // Get image info
    $info = @getimagesize($target_file);
    if (!$info)
        return "Unable to read image file info";

    $mime = $info['mime'];

    // Create image resource based on type
    if ($mime == 'image/jpeg' || $mime == 'image/jpg') {
        $image = @imagecreatefromjpeg($target_file);
    } elseif ($mime == 'image/png') {
        $image = @imagecreatefrompng($target_file);
    } elseif ($mime == 'image/webp') {
        $image = @imagecreatefromwebp($target_file);
    } else {
        return "Unsupported mime type: " . $mime;
    }

    if (!$image)
        return "Failed to create image resource (invalid file format?)";

    $width = imagesx($image);
    $height = imagesy($image);
    $margin = 20;

    // ╔══════════════════════════════════════════════════════╗
    // ║          WATERMARK SETTINGS — Chỉnh tại đây!        ║
    // ╠══════════════════════════════════════════════════════╣
    // ║  BẬT / TẮT (true = bật, false = tắt)               ║
    $wm_show_logo = false;   // Hiện logo MT
    $wm_show_text = true;   // Hiện chữ "MauTranhCNC.com"
    $wm_show_circle = false;  // Hiện nền tròn trắng phía sau logo
    // ║                                                      ║
    // ║  KÍCH THƯỚC & VỊ TRÍ                                ║
    $wm_logo_size = 0.35;   // Logo: tỉ lệ theo cạnh ngắn ảnh (0.0 – 1.0)
    $wm_text_size = 0.08;   // Chữ: tỉ lệ chiều cao cạnh ngắn   (0.0 – 0.2)
    // ║                                                      ║
    // ║  ĐỘ MỜ                                              ║
    $wm_logo_opacity = 20;    // Logo: 0 = ẩn  →  100 = hiện rõ
    $wm_text_alpha = 100;    // Chữ: 0 = rõ   →  127 = ẩn hoàn toàn
    $wm_circle_alpha = 115;   // Nền tròn: 0 = đặc → 127 = ẩn (chỉ dùng khi $wm_show_circle = true)
    // ╚══════════════════════════════════════════════════════╝

    $logo_path = "../home/imgs/logo/mt_logo.png";
    $short_side = min($width, $height);
    $cx = $width / 2;  // tâm ngang ảnh
    $cy = $height / 2;  // tâm dọc ảnh

    imagealphablending($image, true);
    imagesavealpha($image, true);

    // ── 1. LOGO ──────────────────────────────────────────
    if ($wm_show_logo && file_exists($logo_path)) {
        $logo = @imagecreatefrompng($logo_path);
        if ($logo) {
            $lw = imagesx($logo);
            $lh = imagesy($logo);

            $new_lw = (int) ($short_side * $wm_logo_size);
            $new_lh = (int) (($lh / $lw) * $new_lw);
            $lx = (int) (($width - $new_lw) / 2);
            $ly = (int) (($height - $new_lh) / 2);

            // Nền tròn (tùy chọn)
            if ($wm_show_circle) {
                $radius = (int) (max($new_lw, $new_lh) / 2 * 1.2);
                $cc = imagecolorallocatealpha($image, 255, 255, 255, $wm_circle_alpha);
                imagefilledellipse($image, (int) $cx, (int) $cy, $radius * 2, $radius * 2, $cc);
            }

            // Resize logo vào canvas tạm có alpha
            $tmp = imagecreatetruecolor($new_lw, $new_lh);
            imagealphablending($tmp, false);
            imagesavealpha($tmp, true);
            imagefill($tmp, 0, 0, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
            imagecopyresampled($tmp, $logo, 0, 0, 0, 0, $new_lw, $new_lh, $lw, $lh);
            imagedestroy($logo);

            imagecopymerge($image, $tmp, $lx, $ly, 0, 0, $new_lw, $new_lh, $wm_logo_opacity);
            imagedestroy($tmp);
        }
    }

    // ── 2. TEXT ──────────────────────────────────────────
    if ($wm_show_text) {
        $text = "MauTranhCNC.com";
        $font_base = 5; // font lớn nhất của PHP built-in

        // Scale chữ lên theo cạnh ngắn ảnh (vẽ vào canvas nhỏ rồi phóng to)
        $desired_h = max(13, (int) ($short_side * $wm_text_size));
        $base_h = imagefontheight($font_base);   // ~15px
        $scale = max(1, round($desired_h / $base_h));

        $base_tw = strlen($text) * imagefontwidth($font_base);
        $base_th = $base_h;

        // Canvas chứa text gốc
        $tc = imagecreatetruecolor($base_tw, $base_th);
        imagealphablending($tc, false);
        imagesavealpha($tc, true);
        imagefill($tc, 0, 0, imagecolorallocatealpha($tc, 0, 0, 0, 127));

        $tc_white = imagecolorallocate($tc, 255, 255, 255);
        imagestring($tc, $font_base, 0, 0, $text, $tc_white);

        // Phóng to canvas text
        $scaled_tw = (int) ($base_tw * $scale);
        $scaled_th = (int) ($base_th * $scale);
        $ts = imagecreatetruecolor($scaled_tw, $scaled_th);
        imagealphablending($ts, false);
        imagesavealpha($ts, true);
        imagefill($ts, 0, 0, imagecolorallocatealpha($ts, 0, 0, 0, 127));
        imagecopyresampled($ts, $tc, 0, 0, 0, 0, $scaled_tw, $scaled_th, $base_tw, $base_th);
        imagedestroy($tc);

        // Vị trí: căn giữa, bên dưới logo (hoặc giữa ảnh nếu không có logo)
        $tx = (int) (($width - $scaled_tw) / 2);
        $ty = (int) (($height + $short_side * $wm_logo_size) / 2) + 10;

        imagecopymerge(
            $image,
            $ts,
            $tx,
            $ty,
            0,
            0,
            $scaled_tw,
            $scaled_th,
            (int) (100 - ($wm_text_alpha / 127 * 100))
        );
        imagedestroy($ts);
    }


    // Save as JPEG (overwrites original)
    $success = imagejpeg($image, $target_file, 85);
    imagedestroy($image);

    return $success ? true : "Failed to save watermarked image";
}

// -------------------------------------------------------------
// GIẢI THÍCH BẢO MẬT: ĐƯỜNG DẪN TUYỆT ĐỐI (ABSOLUTE PATHS)
// -------------------------------------------------------------
// Khi chạy trên XAMPP hoặc Hosting, dùng đường dẫn tương đối (../) 
// đôi khi bị lỗi nếu script được nạp từ chỗ khác. 
// GIẢI PHÁP: Dùng __DIR__ để khóa cứng vị trí file vật lý.

$upload_dir = realpath(__DIR__ . '/../home/imgs') . DIRECTORY_SEPARATOR;
$logo_path = realpath(__DIR__ . '/../home/imgs/logo/mt_logo.png');
// -------------------------------------------------------------

$error_logs = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. KIỂM TRA CSRF TOKEN
    if (!isset($_POST['csrf_token']) || !Security\verify_csrf_token($_POST['csrf_token'])) {
        sendError("Lỗi bảo mật: CSRF Token không hợp lệ!");
    }

    $des = $_POST['desimg'] ?? "";
    $pronamere = $_POST["nameimg"] ?? "Sản phẩm";
    $protype = $_POST["typeimg"] ?? "Chưa phân loại";

    // Get starting count for numbering
    $stmt_count = $link->prepare('SELECT COUNT(*) AS total FROM products WHERE protype = ?');
    $stmt_count->bind_param("s", $protype);
    $stmt_count->execute();
    $res_count = $stmt_count->get_result();
    $row_count = $res_count->fetch_assoc();
    $countitems = (int) $row_count['total'];
    $stmt_count->close();

    if (empty($_FILES)) {
        sendError("No files received. Please select images to upload.");
    }

    // Process each file
    foreach ($_FILES as $key => $file_info) {
        if ($file_info['error'] !== UPLOAD_ERR_OK) {
            $error_logs[] = "Lỗi upload file: " . $file_info['error'];
            continue;
        }

        // 2. KIỂM TRA DUNG LƯỢNG (Max 10MB)
        if ($file_info['size'] > 10 * 1024 * 1024) {
             $error_logs[] = "File quá lớn (Tối đa 10MB): " . Security\h($file_info['name']);
             continue;
        }

        // 3. KIỂM TRA MIME TYPE THỰC TẾ (Magic Bytes)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $real_mime = $finfo->file($file_info['tmp_name']);
        $allowed_mimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($real_mime, $allowed_mimes)) {
            $error_logs[] = "Định dạng file không hợp lệ (Chỉ chấp nhận JPG/PNG/WebP): " . Security\h($file_info['name']);
            continue;
        }

        // 4. KIỂM TRA KÍCH THƯỚC ẢNH (Pixel - Chống Decompression Bomb)
        $img_info = @getimagesize($file_info['tmp_name']);
        if (!$img_info || $img_info[0] > 10000 || $img_info[1] > 10000) {
            $error_logs[] = "Kích thước ảnh quá lớn hoặc không hợp lệ: " . Security\h($file_info['name']);
            continue;
        }

        $countitems++;
        $display_name = $pronamere . " " . $countitems;

        // Generate unique filename
        $final_file_name = time() . "_" . rand(1000, 9999) . ".jpg";
        $target_path = $upload_dir . $final_file_name;

        if (move_uploaded_file($file_info['tmp_name'], $target_path)) {

            // Apply Watermark
            $wm_status = addWatermark($target_path);
            if ($wm_status !== true) {
                $error_logs[] = "Watermark warning for $display_name: " . $wm_status;
            }

            // Insert into Database
            $sql_insert = 'INSERT INTO products(proname, protype, prourl, description) VALUES (?, ?, ?, ?)';
            $stmt_ins = $link->prepare($sql_insert);
            if ($stmt_ins) {
                $stmt_ins->bind_param('ssss', $display_name, $protype, $final_file_name, $des);
                if (!$stmt_ins->execute()) {
                    $error_logs[] = "DB Error for $display_name: " . $stmt_ins->error;
                    // Delete uploaded file if DB record creation failed
                    if (file_exists($target_path)) unlink($target_path);
                }
                $stmt_ins->close();
            } else {
                $error_logs[] = "Failed to prepare DB statement for $display_name";
            }
        } else {
            $error_logs[] = "Failed to move uploaded file: " . $file_info['name'];
        }
    }

    if (!empty($error_logs)) {
        echo json_encode([
            "status" => "partial_success",
            "message" => "Finished with some issues.",
            "errors" => $error_logs
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "message" => "All images uploaded and watermarked successfully!"
        ]);
    }
} else {
    sendError("Invalid request method.");
}

$link->close();
