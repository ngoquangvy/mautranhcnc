<?php
require_once "../includes/connectdb.php";

$success_message = "";
$zalo_link = "";
$zalo_text = ""; // Khởi tạo biến để tránh lỗi Warning khi bị chặn Bot

// -------------------------------------------------------------
// BẢO MẬT: TIME TRAP (Bẫy thời gian)
// -------------------------------------------------------------
// Mục tiêu: Ghi lại thời điểm người dùng bắt đầu mở trang thanh toán. 
// Bot thường gửi đơn ngay lập tức, con người sẽ mất ít nhất vài giây để điền.
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $_SESSION['cart_load_time'] = time();
    // Tạo Token nạp đơn duy nhất để chống lặp đơn (Back button / Double click)
    $_SESSION['order_submit_token'] = bin2hex(random_bytes(16));
}
// -------------------------------------------------------------

// Xử lý Gửi Form Backend
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['customer_name'])) {

    // -------------------------------------------------------------
    // GIẢI THÍCH BẢO MẬT: XÁC THỰC HAI LỚP (CSRF & reCAPTCHA)
    // -------------------------------------------------------------
    // 1. Chống Spam Bot: reCAPTCHA ngăn chặn việc gửi đơn hàng tự động.
    // 2. Chống CSRF: Đảm bảo dữ liệu chỉ được gửi từ chính form này.

    $recaptcha_success = Security\verify_recaptcha($_POST['g-recaptcha-response'] ?? '');
    $csrf_success = Security\verify_csrf_token($_POST['csrf_token'] ?? '');

    // -------------------------------------------------------------
    // [CODE MỚI - BẢO MẬT MỀM]
    // -------------------------------------------------------------
    $is_bot = false;
    $bot_reason = "";

    // 1. Kiểm tra Honeypot (Bot thường tự điền các ô ẩn)
    if (!empty($_POST['email_confirm_api'])) {
        $is_bot = true;
        $bot_reason = "Honeypot detected";
    }

    // 2. Kiểm tra Time Trap (Bot nạp form quá nhanh < 3 giây)
    $load_time_session = $_SESSION['cart_load_time'] ?? 0;
    $load_time_post = (int) ($_POST['form_token_time'] ?? 0);

    // Ưu tiên lấy thời gian lớn nhất (Gần nhất - Nghiêm ngặt nhất)
    $load_time = ($load_time_session > 0) ? max($load_time_session, $load_time_post) : $load_time_post;

    $submit_duration = time() - $load_time;
    if ($submit_duration < 3) {
        $is_bot = true;
        $bot_reason = "Time trap ($submit_duration s)";
    }

    // 3. Rate Limiting (Giới hạn tối thiểu giữa 2 lần nạp đơn để tránh DoS)
    $last_submit = $_SESSION['last_submit_time'] ?? 0;

    $submit_token_post = $_POST['submit_token'] ?? '';
    $submit_token_session = $_SESSION['order_submit_token'] ?? '';
    $is_duplicate = ($submit_token_post !== $submit_token_session || empty($submit_token_session));

    if (time() - $last_submit < 30) {
        $success_message = "<span style='color:orange;'>Bạn gửi đơn hơi nhanh. Vui lòng đợi 30 giây để tiếp tục nhé!</span>";
    } elseif ($is_duplicate) {
        // Nếu là đơn trùng (do nhấn back hoặc refresh cũ), chúng ta không báo lỗi mà chuyển hướng thẳng đến trang thành công của đơn trước đó.
        if (isset($_SESSION['last_order_success'])) {
            header("Location: cart.php?status=success&msg=already_done");
            exit;
        }
        $success_message = "<span style='color:red;'>Lỗi: Mã bảo mật đơn hàng đã hết hạn hoặc đã được gửi.</span>";
    } elseif (!$csrf_success) {
        $success_message = "<span style='color:red;'>Lỗ: CSRF Token không hợp lệ. Vui lòng thử lại.</span>";
    } elseif (Security\is_recaptcha_enabled() && !$recaptcha_success) {
        $success_message = "<span style='color:red;'>Lỗi: Vui lòng xác thực bạn không phải là robot!</span>";
    } else {
        // [LUỒNG XỬ LÝ CHÍNH - LUÔN LƯU ĐƠN]
        $_SESSION['last_submit_time'] = time();
        unset($_SESSION['cart_load_time']);

        $name = Security\h(mb_substr(trim($_POST['customer_name']), 0, 100));
        $phone = Security\h(mb_substr(trim($_POST['customer_phone']), 0, 100));
        $note = Security\h(mb_substr(trim($_POST['order_note']), 0, 500));

        // THỰC HIỆN "SILENT FLAGGING" - Đánh dấu nhưng không chặn
        if ($is_bot) {
            $note = "[Hệ thống: Nghi ngờ Bot ($bot_reason)] " . ($note ?? '');
        }

        // DEBUG: Ghi lại log để kiểm tra tại sao không gắn cờ (Chỉ dùng khi test)
        // error_log("DEBUG MTCNC: is_bot=" . ($is_bot?'TRUE':'FALSE') . " | duration=$submit_duration | reason=$bot_reason");

        $cart_data = isset($_POST['cart_data']) ? json_decode($_POST['cart_data'], true) : [];

        /*
        // ---------------------------------------------------------
        // MÃ NGUỒN CŨ (CHƯA CÓ BẢO MẬT SPAM) - CŨ ĐỂ NGHIÊN CỨU:
        // ---------------------------------------------------------
        // Nguy cơ: Hacker có thể bypass form này để đặt hàng vô hạn 
        // và làm tràn bộ nhớ Telegram của Admin.

        $name = Security\h(trim($_POST['customer_name']));
        $cart_data = isset($_POST['cart_data']) ? json_decode($_POST['cart_data'], true) : [];
        ... [Xử lý lưu và gửi Notify] ...
        */

        if ($cart_data && is_array($cart_data) && count($cart_data) > 0) {
            // Lưu vào bảng orders
            $stmt = $link->prepare("INSERT INTO orders (customer_name, customer_phone, note) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sss", $name, $phone, $note);
                if ($stmt->execute()) {
                    $order_id = $stmt->insert_id;

                    // Lưu vào bảng order_items
                    $item_stmt = $link->prepare("INSERT INTO order_items (order_id, product_id, product_name) VALUES (?, ?, ?)");
                    // Chuẩn bị câu lệnh lấy tên thật từ DB
                    $check_stmt = $link->prepare("SELECT proname FROM products WHERE id = ?");

                    $zalo_text = "Chào Shop, tôi muốn đặt các mẫu CNC sau:\n";
                    foreach ($cart_data as $item) {
                        $pid = (int) ($item['id'] ?? 0);

                        $actual_name = "Sản phẩm không tồn tại";
                        // Truy vấn tên thật
                        $check_stmt->bind_param("i", $pid);
                        $check_stmt->execute();
                        $check_res = $check_stmt->get_result();
                        if ($row_p = $check_res->fetch_assoc()) {
                            $actual_name = $row_p['proname'];
                        }

                        $pname = Security\h($actual_name);
                        $item_stmt->bind_param("iss", $order_id, $pid, $pname);
                        $item_stmt->execute();

                        $zalo_text .= "- $pname (Mã: $pid)\n";
                    }
                    $check_stmt->close();

                    $zalo_text .= "\nNgười đặt: $name ($phone)";
                    if (!empty($note)) {
                        $zalo_text .= "\nGhi chú: $note";
                    }

                    $zalo_link = "https://zalo.me/0338790560?text=" . rawurlencode($zalo_text);
                    $success_message = "Đơn hàng #$order_id của bạn đã được ghi nhận!";

                    // THÔNG BÁO ADMIN THỜI GIAN THỰC (REAL-TIME)
                    $tg_msg = "<b>🔔 CÓ ĐƠN HÀNG MỚI (#$order_id)</b>\n";
                    $tg_msg .= "👤 <b>Khách hàng:</b> $name\n";
                    $tg_msg .= "📞 <b>Số điện thoại:</b> $phone\n";
                    $tg_msg .= "📦 <b>Chi tiết:</b>\n" . $zalo_text;

                    // -------------------------------------------------------------
                    // CHIẾN LƯỢC BẢO MẬT MỀM (SILENT NOTIFICATION)
                    // -------------------------------------------------------------
                    // Chỉ gửi thông báo nếu KHÔNG bị nghi ngờ là Bot.
                    // Điều này giúp Inbox của bạn luôn sạch sẽ, đơn spam vẫn lưu để xem sau.
                    if (!$is_bot) {
                        Security\notify_admin($tg_msg);
                    }
                    // -------------------------------------------------------------

                    // [MỚI] CHỐNG LẶP ĐƠN HÀNG KHI REFRESH (PRG PATTERN)
                    // Lưu thông tin vào Session để hiển thị sau khi chuyển hướng
                    $_SESSION['last_order_success'] = [
                        'id' => $order_id,
                        'message' => $success_message,
                        'zalo_text' => $zalo_text,
                        'zalo_link' => $zalo_link,
                        'tg_msg' => $tg_msg,
                        'notif_payload' => Security\generate_notification_payload($tg_msg),
                        'timestamp' => time()
                    ];

                    // [QUAN TRỌNG] HỦY TOKEN SAU KHI THÀNH CÔNG ĐỂ CHỐNG LẶP
                    unset($_SESSION['order_submit_token']);

                    header("Location: cart.php?status=success");
                    exit;
                }
                $stmt->close();
            }
        }
    }
}

// [MỚI] KIỂM TRA TRẠNG THÁI THÀNH CÔNG TỪ REDIRECT
$order_show_success = false;
if (isset($_GET['status']) && $_GET['status'] === 'success' && isset($_SESSION['last_order_success'])) {
    // Kiểm tra tính hiệu lực của session (trong vòng 5 phút)
    if (time() - $_SESSION['last_order_success']['timestamp'] < 300) {
        $order_show_success = true;
        $success_message = $_SESSION['last_order_success']['message'];
        $zalo_text = $_SESSION['last_order_success']['zalo_text'];
        $zalo_link = $_SESSION['last_order_success']['zalo_link'];
        $notif_payload = $_SESSION['last_order_success']['notif_payload'];
    }
}

// Lấy danh sách loại sản phẩm để hiển thị trên menu
$show_protype = "";
$sql_types = "SELECT protype from products group by protype";
$res_types = $link->query($sql_types);
if ($res_types && $res_types->num_rows > 0) {
    while ($row = $res_types->fetch_assoc()) {
        $cleanType = Security\h($row["protype"]);
        $show_protype .= '<a href="protype.php?id=' . urlencode($row["protype"]) . '"><p class="nav-link type-link type">' . $cleanType . '</p></a>';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - Mẫu CNC</title>
    <link rel="shortcut icon" href="imgs/logo/mt_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/cart.css" rel="stylesheet">
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/cart.js"></script>
    <!-- THƯ VIỆN GOOGLE reCAPTCHA (Xác thực Bot) -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --accent-color: #27ae60;
            --bg-light: #f8f9fa;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f5f7;
            padding-top: 100px;
        }

        .headerr {
            z-index: 1050;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: rgba(33, 37, 41, 0.85) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
        }

        .cart-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        .cart-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            padding: 30px;
            overflow: hidden;
        }

        .cart-title {
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 30px;
            color: var(--primary-color);
        }

        .cart-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            transition: all 0.3s;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-img {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
            background: #eee;
        }

        .item-details {
            flex: 1;
            padding-left: 20px;
        }

        .item-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
            color: #2d3436;
        }

        .item-type {
            font-size: 0.85rem;
            color: #636e72;
        }

        .btn-remove {
            background: #fff0f0;
            color: #ff7675;
            border: none;
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-remove:hover {
            background: #ff7675;
            color: white;
            transform: scale(1.05);
        }

        .empty-cart {
            text-align: center;
            padding: 50px 0;
        }

        .empty-cart i {
            font-size: 4rem;
            color: #dfe6e9;
            margin-bottom: 20px;
        }

        .order-form {
            margin-top: 40px;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 20px;
            border: 1px solid #dfe6e9;
            background: #fdfdfd;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(39, 174, 96, 0.1);
            outline: none;
        }

        .btn-submit {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 15px;
            font-weight: 600;
            width: 100%;
            font-size: 1.1rem;
            margin-top: 20px;
            box-shadow: 0 10px 20px rgba(39, 174, 96, 0.3);
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(39, 174, 96, 0.4);
        }

        /* Giao diện Thành công */
        .success-box {
            text-align: center;
            padding: 40px 20px;
        }

        .success-box i {
            font-size: 5rem;
            color: var(--accent-color);
            margin-bottom: 20px;
        }

        .success-box h2 {
            font-weight: 700;
            color: var(--primary-color);
        }

        .btn-zalo {
            display: inline-block;
            background: #0068ff;
            color: white;
            border-radius: 15px;
            padding: 15px 30px;
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 20px;
            text-decoration: none;
            box-shadow: 0 10px 20px rgba(0, 104, 255, 0.3);
            transition: all 0.3s;
        }

        .btn-zalo:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 104, 255, 0.4);
            text-decoration: none;
        }

        /* Responsive Mobile Tweak */
        @media (max-width: 768px) {
            .success-box {
                padding: 20px 10px;
            }

            .success-box i {
                font-size: 3.5rem;
            }

            .success-box h2 {
                font-size: 1.6rem;
            }

            .mobile-stack-btn {
                width: 100% !important;
                margin-top: 10px !important;
                padding: 12px !important;
            }
        }

        /* ─────────────────────────────────────────────────────────────
           BẢO MẬT: HONEYPOT CSS (Trường bẫy Bot)
           ───────────────────────────────────────────────────────────── */
        .hp-field {
            display: none !important;
            visibility: hidden !important;
        }
    </style>
</head>

<body>

    <header class="headerr">
        <nav class="navbar navbar-expand-md navbar-dark bg-dark d-flex">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <img src="imgs/logo/mt_logo.png" alt="Logo">
                    <span>Mẫu CNC</span>
                </a>
                <ul class="navbar-nav ml-auto d-none d-md-flex">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link cart-nav-icon" href="cart.php">
                            <i class="fa fa-shopping-cart" style="font-size: 1.2rem;"></i>
                            <span class="cart-badge-count">0</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="container cart-container">
        <div class="cart-card">

            <?php if ($order_show_success): ?>
                <!-- Đặt hàng thành công -->
                <div class="success-box">
                    <script>
                        (function () {
                            const data = <?php echo json_encode($notif_payload); ?>;
                            const orderId = "<?php echo $_SESSION['last_order_success']['id']; ?>";

                            // Chỉ gửi thông báo một lần duy nhất (dùng localStorage để đánh dấu)
                            if (data && data.url && !localStorage.getItem('notif_sent_' + orderId)) {
                                fetch(data.url, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'x-webhook-signature': data.signature
                                    },
                                    body: data.payload
                                })
                                    .then(async (res) => {
                                        const text = await res.text();
                                        if (!res.ok) throw new Error(text || ('HTTP ' + res.status));
                                        localStorage.setItem('notif_sent_' + orderId, 'true');
                                        return text;
                                    })
                                    .then(() => console.log('System: Order notification sent bridge successfully.'))
                                    .catch(err => console.error('System: Notification bridge failed.', err));
                            }
                        })();
                    </script>
                    <i class="fa fa-check-circle"></i>
                    <h2><?php echo $success_message; ?></h2>
                    <p class="text-muted mt-2">Chúng tôi sẽ liên hệ với bạn sớm nhất qua số điện thoại/Zalo nếu có. Cảm ơn
                        bạn đã đặt hàng!.</p>

                    <div class="text-left mt-4"
                        style="background: #f8f9fa; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; max-width: 600px; margin: 0 auto;">
                        <p class="font-weight-bold mb-2 text-dark"><i class="fa fa-file-text-o mr-2"></i>Nội dung đơn hàng
                            (Vui lòng Copy dòng dưới gửi qua Zalo):</p>
                        <textarea id="zaloMessageText" class="form-control mb-3" rows="6" readonly
                            style="background: white; cursor: text;"><?php echo htmlspecialchars($zalo_text ?? '', ENT_QUOTES); ?></textarea>

                        <div class="d-flex flex-column flex-md-row justify-content-center align-items-center"
                            style="gap: 15px;">
                            <button type="button" class="btn btn-outline-secondary mobile-stack-btn"
                                onclick="copyZaloText()" style="font-weight: 600; border-radius: 10px; padding: 10px 25px;">
                                <i class="fa fa-copy mr-2"></i>Sao chép nội dung
                            </button>
                            <a href="<?php echo $zalo_link; ?>" target="_blank" class="btn-zalo mobile-stack-btn"
                                style="margin-top: 0; padding: 10px 25px;">
                                Mở Zalo Chủ Shop <i class="fa fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <script>
                    // Xóa rỗng giỏ hàng sau khi chốt đơn thành công
                    localStorage.removeItem(CART_KEY);
                    // Cập nhật lại biểu tượng nốt đỏ
                    updateCartCounter();

                    function copyZaloText() {
                        var copyText = document.getElementById("zaloMessageText");
                        copyText.select();
                        copyText.setSelectionRange(0, 99999); /* Đối với mobile */
                        document.execCommand("copy");
                        alert("Đã sao chép nội dung! Bạn hãy chọn Mở Zalo và dán (Paste) vào nhé.");
                    }
                </script>

            <?php else: ?>
                <!-- Giao diện Giỏ Hàng thông thường -->
                <h1 class="cart-title"><i class="fa fa-shopping-basket mr-2"></i>Giỏ hàng mẫu CNC</h1>

                <div id="cart-items-list">
                    <!-- Javascript will render items here -->
                    <div class="empty-cart" id="empty-state">
                        <i class="fa fa-shopping-cart"></i>
                        <p>Giỏ hàng đang trống.</p>
                        <a href="index.php" class="btn btn-outline-success rounded-pill px-4">Xem mẫu ngay</a>
                    </div>
                </div>

                <div id="order-section" style="display: none;">
                    <hr class="my-5">
                    <h3 class="font-weight-bold mb-4">Thông tin liên hệ báo giá</h3>
                    <form id="checkoutForm" action="cart.php" method="POST" onsubmit="prepareCartData()">
                        <input type="hidden" name="cart_data" id="cart_data_input">
                        <!-- CSRF TOKEN (Bảo vệ khỏi việc gửi form giả mạo từ trang khác) -->
                        <input type="hidden" name="csrf_token" value="<?php echo Security\generate_csrf_token(); ?>">

                        <!-- [MỚI] SUBMIT TOKEN (Chống lặp đơn khi nhấn Back / Double click) -->
                        <input type="hidden" name="submit_token"
                            value="<?php echo $_SESSION['order_submit_token'] ?? ''; ?>">

                        <!-- ─────────────────────────────────────────────────────────────
                             BẢO MẬT: HONEYPOT INPUT
                             ─────────────────────────────────────────────────────────────
                             Nếu Bot tự động điền vào ô này, hệ thống sẽ coi là Spam.
                             Người dùng thật sẽ không thấy ô này do CSS .hp-field phía trên. 
                        -->
                        <div class="hp-field">
                            <input type="text" name="email_confirm_api" value="" tabindex="-1" autocomplete="off">
                        </div>

                        <!-- ─────────────────────────────────────────────────────────────
                             BẢO MẬT: FORM TOKEN TIME (Bổ trợ Time Trap)
                             ───────────────────────────────────────────────────────────── 
                        -->
                        <input type="hidden" name="form_token_time" value="<?php echo time(); ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="small font-weight-bold text-muted">Họ và Tên</label>
                                <input type="text" class="form-control" name="customer_name" maxlength="100"
                                    placeholder="Nhập tên của bạn..." required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="small font-weight-bold text-muted">Số điện thoại / Zalo</label>
                                <input type="tel" class="form-control" name="customer_phone" maxlength="100"
                                    placeholder="Số điện thoại nhận file..." required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="small font-weight-bold text-muted">Ghi chú (Kích thước, yêu cầu thêm...)</label>
                            <textarea class="form-control" name="order_note" rows="3" maxlength="500"
                                placeholder="Ví dụ: Tôi cần mẫu này kích thước 1m2..."></textarea>
                        </div>

                        <!-- GOOGLE reCAPTCHA V2 Widget -->
                        <!-- Nhớ lấy SITE_KEY từ tệp .env -->
                        <?php if (Security\is_recaptcha_enabled()): ?>
                            <div class="mb-4 d-flex justify-content-center">
                                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn-submit">
                            <i class="fa fa-whatsapp mr-2"></i>Gửi đơn hàng & Nhận báo giá
                        </button>
                        <p class="text-center text-muted small mt-3">
                            * Chúng tôi sẽ lưu đơn hàng và chuyển bạn đến Zalo qua số 0338790560.
                        </p>
                    </form>
                </div>

                <script>
                    // Hàm chèn JSON Giỏ hàng vào Input ẩn trước khi Submit Form để PHP nhận
                    function prepareCartData() {
                        const cartStr = localStorage.getItem(CART_KEY);
                        document.getElementById('cart_data_input').value = cartStr;
                    }

                    // Hàm render danh sách từ LocalStorage
                    function renderCartItems() {
                        const cart = getCart();
                        const container = document.getElementById('cart-items-list');
                        const orderSection = document.getElementById('order-section');

                        if (!cart || cart.length === 0) {
                            container.innerHTML = `
                            <div class="empty-cart">
                                <i class="fa fa-shopping-cart"></i>
                                <p>Giỏ hàng của bạn đang trống.</p>
                                <a href="index.php" class="btn btn-outline-success rounded-pill px-4">Quay lại xem mẫu</a>
                            </div>`;
                            orderSection.style.display = 'none';
                            return;
                        }

                        orderSection.style.display = 'block';
                        let html = '';
                        cart.forEach(item => {
                            html += `
                            <div class="cart-item">
                                <img src="imgs/${item.image}" class="item-img" alt="${item.name}">
                                <div class="item-details">
                                    <h4 class="item-name">${item.name}</h4>
                                    <span class="item-type">${item.type}</span>
                                </div>
                                <button type="button" class="btn-remove" onclick="removeFromCart('${item.id}')">
                                    <i class="fa fa-trash"></i> Xóa
                                </button>
                            </div>
                        `;
                        });
                        container.innerHTML = html;
                        updateCartCounter();
                    }

                    // Khởi động trang
                    document.addEventListener('DOMContentLoaded', renderCartItems);
                </script>
            <?php endif; ?>

        </div>
    </div>

</body>

</html>