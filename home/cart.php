<?php
require_once "../includes/connectdb.php";
require_once "../includes/config_site.php";
require_once "../includes/security.php";
// logic lấy danh mục sản phẩm để nạp vào Header (header_site.php cần biến $show_protype này)
$show_protype = "";
$sql_types = "SELECT protype FROM products GROUP BY protype";
$res_types = $link->query($sql_types);
if ($res_types && $res_types->num_rows > 0) {
    while ($row_t = $res_types->fetch_assoc()) {
        $safeType = Security\h($row_t["protype"]);
        $urlType = urlencode($row_t["protype"]);
        $show_protype .= '<a href="protype.php?id=' . $urlType . '"><p class="nav-link type-link type" value="' . $safeType . '">' . $safeType . '</p></a>';
    }
}


// MT_CNC_AUTH is defined in security.php (at the end) because it calls secure_session_start()
// But for safety, we ensure it's here at the very top of cart.php
if (!defined('MT_CNC_AUTH')) define('MT_CNC_AUTH', true);

$success_message = "";
$zalo_link = "";
$zalo_text = ""; 

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $_SESSION['cart_load_time'] = time();
    $_SESSION['order_submit_token'] = bin2hex(random_bytes(16));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['customer_name'])) {
    $recaptcha_success = Security\verify_recaptcha($_POST['g-recaptcha-response'] ?? '');
    $csrf_success = Security\verify_csrf_token($_POST['csrf_token'] ?? '');
    $is_bot = false;
    $bot_reason = "";
    // LỚP 1: Honeypot - Nếu ô input ẩn này có dữ liệu -> Chắc chắn là Bot
    if (!empty($_POST['email_confirm_api'])) { $is_bot = true; $bot_reason = "Honeypot"; }
    // LỚP 2: Time Trap - Nếu gửi đơn nhanh hơn 3 giây từ lúc load trang -> Nghi vấn Bot
    $load_time = $_SESSION['cart_load_time'] ?? (int)($_POST['form_token_time'] ?? 0);
    if (time() - $load_time < 3) { $is_bot = true; $bot_reason = "Time trap"; }
    
    $last_submit = $_SESSION['last_submit_time'] ?? 0;
    $submit_token_post = $_POST['submit_token'] ?? '';
    $submit_token_session = $_SESSION['order_submit_token'] ?? '';
    
    if (time() - $last_submit < 30) {
        $success_message = "<span style='color:orange;'>Bạn gửi đơn hơi nhanh.</span>";
    } elseif ($submit_token_post !== $submit_token_session || empty($submit_token_session)) {
        if (isset($_SESSION['last_order_success'])) { header("Location: cart.php?status=success"); exit; }
        $success_message = "<span style='color:red;'>Lỗi: Mã bảo mật hết hạn.</span>";
    } elseif (!$csrf_success) {
        $success_message = "<span style='color:red;'>Lỗi: CSRF Token không hợp lệ.</span>";
    } else {
        $_SESSION['last_submit_time'] = time();
        $name = Security\h(mb_substr(trim($_POST['customer_name']), 0, 100));
        $phone = Security\h(mb_substr(trim($_POST['customer_phone']), 0, 100));
        $note = Security\h(mb_substr(trim($_POST['order_note']), 0, 500));
        if ($is_bot) { $note = "[Hệ thống: Bot ($bot_reason)] " . ($note ?? ''); }
        $cart_data = isset($_POST['cart_data']) ? json_decode($_POST['cart_data'], true) : [];
        if ($cart_data && is_array($cart_data)) {
            $stmt = $link->prepare("INSERT INTO orders (customer_name, customer_phone, note) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sss", $name, $phone, $note);
                if ($stmt->execute()) {
                    $order_id = $stmt->insert_id;
                    $item_stmt = $link->prepare("INSERT INTO order_items (order_id, product_id, product_name) VALUES (?, ?, ?)");
                    $check_stmt = $link->prepare("SELECT proname FROM products WHERE id = ?");
                    $ztel = "Chào Shop, tôi muốn đặt các mẫu CNC sau:\n";
                    foreach ($cart_data as $item) {
                        $pid = (int)$item['id'];
                        $check_stmt->bind_param("i", $pid); $check_stmt->execute();
                        $check_res = $check_stmt->get_result(); $actual_name = ($rp = $check_res->fetch_assoc()) ? $rp['proname'] : "SP #$pid";
                        $pname = Security\h($actual_name);
                        $item_stmt->bind_param("iss", $order_id, $pid, $pname); $item_stmt->execute();
                        $ztel .= "- $pname (Mã: $pid)\n";
                    }
                    $ztel .= "\nNgười đặt: $name ($phone)";
                    $zlink = "https://zalo.me/0338790560?text=" . rawurlencode($ztel);
                    $tg_msg = "<b>🔔 ĐƠN MỚI #$order_id</b>\n👤 $name\n📞 $phone\n" . $ztel;
                    if (!$is_bot) { Security\notify_admin($tg_msg); }
                    
                    // Generate payload for the notification bridge
                    $notif_payload = Security\generate_notification_payload($tg_msg);
                    
                    $_SESSION['last_order_success'] = [
                        'id' => $order_id, 
                        'message' => "Đã lưu đơn #$order_id", 
                        'zalo_text' => $ztel, 
                        'zalo_link' => $zlink, 
                        'timestamp' => time(), 
                        'notif_url' => $notif_payload['url'] ?? '',
                        'notif_payload' => $notif_payload['payload'] ?? '',
                        'notif_signature' => $notif_payload['signature'] ?? ''
                    ];
                    unset($_SESSION['order_submit_token']);
                    header("Location: cart.php?status=success"); exit;
                }
            }
        }
    }
}

$order_show_success = false;
if (isset($_GET['status']) && $_GET['status'] === 'success' && isset($_SESSION['last_order_success'])) {
    if (time() - $_SESSION['last_order_success']['timestamp'] < 300) {
        $order_show_success = true;
        $success_message = $_SESSION['last_order_success']['message'];
        $zalo_text = $_SESSION['last_order_success']['zalo_text'];
        $zalo_link = $_SESSION['last_order_success']['zalo_link'];
        $notif_url = $_SESSION['last_order_success']['notif_url'] ?? '';
        $notif_payload = $_SESSION['last_order_success']['notif_payload'] ?? '';
        $notif_signature = $_SESSION['last_order_success']['notif_signature'] ?? '';
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
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        
        body { font-family: 'Inter', sans-serif; background-color: #f5f5f7; padding-top: 0; }
        
        .hp-field { display: none !important; visibility: hidden !important; }
        .cart-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); }
        .cart-title { font-weight: 800; color: #1a1a1a; margin-bottom: 30px; text-align: center; }
        .cart-item { display: flex; align-items: center; padding: 20px 0; border-bottom: 1px solid #edf2f7; }
        .item-img { width: 80px; height: 80px; object-fit: cover; border-radius: 12px; margin-right: 20px; }
        .item-details { flex: 1; }
        .item-name { font-weight: 700; color: #2d3748; }
        .btn-remove { color: #e53e3e; background: #fff5f5; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; }
        .btn-submit { background: #25d366; color: white; border: none; width: 100%; padding: 16px; border-radius: 12px; font-weight: 700; font-size: 1.1rem; margin-top: 20px; transition: all 0.3s; }
        .btn-submit:hover { background: #128c7e; transform: translateY(-2px); }
        @media (max-width: 768px) { .cart-container { padding: 40px 20px; } .mobile-stack-btn { width: 100%; } }
    </style>
</head>
<body>
    <header class="headerr"><?php include "header_site.php"; ?></header>
    <div class="container my-5">
        <div class="cart-container">
            <?php if ($order_show_success): ?>
                <div class="text-center py-5">
                    <script>
                        (function() {
                            const orderId = '<?php echo $_SESSION['last_order_success']['id'] ?? ''; ?>';
                            const url = '<?php echo $notif_url; ?>';
                            const payload = <?php echo json_encode($notif_payload); ?>;
                            const signature = '<?php echo $notif_signature; ?>';
                            
                            if (orderId && url && !localStorage.getItem('notif_sent_' + orderId)) {
                                fetch(url, {
                                    method: 'POST',
                                    headers: { 
                                        'Content-Type': 'application/json',
                                        'x-webhook-signature': signature
                                    },
                                    body: payload
                                })
                                .then(res => { if (res.ok) localStorage.setItem('notif_sent_' + orderId, 'true'); })
                                .catch(err => console.error('Notification failed.', err));
                            }
                        })();
                    </script>
                    <div class="mb-4"><i class="fa fa-check-circle text-success" style="font-size: 5rem;"></i></div>
                    <h2 class="font-weight-bold mb-3"><?php echo $success_message; ?></h2>
                    <p class="text-muted mb-4">Cảm ơn bạn! Đơn hàng đã được lưu. Hãy Copy gửi qua Zalo shop nhé.</p>
                    
                    <div class="alert alert-secondary text-left mb-4 d-inline-block w-100" style="background: #f8f9fa; border: 1px dashed #ccc; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <p class="font-weight-bold mb-2 text-dark"><i class="fa fa-file-text-o mr-2"></i>Nội dung đơn hàng (Vui lòng Copy dòng dưới gửi qua Zalo):</p>
                        <textarea id="zaloMessageText" class="form-control mb-3" rows="6" readonly style="background: white; cursor: text; border-radius: 10px;"><?php echo htmlspecialchars($zalo_text ?? '', ENT_QUOTES); ?></textarea>
                        
                        <div class="d-flex flex-column flex-md-row justify-content-center align-items-center" style="gap: 15px;">
                            <button type="button" class="btn btn-outline-secondary mobile-stack-btn" onclick="copyZaloText()" style="border-radius: 30px; padding: 8px 25px; font-weight: 600;">
                                <i class="fa fa-copy mr-2"></i>Sao chép nội dung
                            </button>
                            <a href="<?php echo $zalo_link; ?>" target="_blank" class="btn btn-primary mobile-stack-btn" style="background:#25d366; border:none; border-radius: 30px; padding: 10px 25px; font-weight: 600;">
                                Mở Zalo Chủ Shop <i class="fa fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                    <a href="index.php" class="d-block mt-4 text-muted">Quay lại trang chủ</a>
                </div>
                <script>
                    localStorage.removeItem('mt_cart_v1'); updateCartCounter();
                    function copyZaloText() {
                        var copyText = document.getElementById("zaloMessageText");
                        copyText.select();
                        copyText.setSelectionRange(0, 99999);
                        document.execCommand("copy");
                        alert("Đã sao chép nội dung! Bạn hãy chọn Mở Zalo và dán (Paste) vào nhé.");
                    }
                </script>
            <?php else: ?>
                <h1 class="cart-title"><i class="fa fa-shopping-basket mr-2"></i>Giỏ hàng CNC</h1>
                <div id="cart-items-list">
                    <div class="empty-cart text-center py-5">Giỏ hàng trống. <a href="index.php">Xem mẫu ngay</a></div>
                </div>
                <div id="order-section" style="display:none;">
                    <hr class="my-5"><h3 class="font-weight-bold mb-4">Thông tin báo giá</h3>
                    <form id="checkoutForm" action="cart.php" method="POST" onsubmit="prepareCartData()">
                        <input type="hidden" name="cart_data" id="cart_data_input">
                        <input type="hidden" name="csrf_token" value="<?php echo Security\generate_csrf_token(); ?>">
                        <input type="hidden" name="submit_token" value="<?php echo $_SESSION['order_submit_token'] ?? ''; ?>">
                        <div class="hp-field"><input type="text" name="email_confirm_api"></div>
                        <input type="hidden" name="form_token_time" value="<?php echo time(); ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="small font-weight-bold">Họ Tên</label><input type="text" class="form-control" name="customer_name" required></div>
                            <div class="col-md-6 mb-3"><label class="small font-weight-bold">Số điện thoại</label><input type="tel" class="form-control" name="customer_phone" required></div>
                        </div>
                        <div class="mb-3"><label class="small font-weight-bold">Ghi chú</label><textarea class="form-control" name="order_note" rows="3"></textarea></div>
                        <button type="submit" class="btn-submit">Chốt đơn hàng & Nhận báo giá</button>
                    </form>
                </div>
                
                <script>
                    /** 
                     * BẢO MỆT FRONTEND: 
                     * Hàm escapeHtml giúp ngăn chặn mã độc Javascript được nạp từ localStorage.
                     */
                    function escapeHtml(text) {
                        const div = document.createElement('div');
                        div.textContent = text;
                        return div.innerHTML;
                    }

                    function prepareCartData() { document.getElementById('cart_data_input').value = localStorage.getItem('mt_cart_v1'); }
                    function renderCartItems() {
                        const cart = JSON.parse(localStorage.getItem('mt_cart_v1') || '[]');
                        const container = document.getElementById('cart-items-list');
                        const orderSection = document.getElementById('order-section');
                        if (cart.length === 0) { container.innerHTML = '<div class="text-center py-5">Giỏ hàng trống.</div>'; orderSection.style.display = 'none'; return; }
                        orderSection.style.display = 'block'; let html = '';
                        cart.forEach(item => {
                            const name = escapeHtml(item.name);
                            const type = escapeHtml(item.type);
                            const img = escapeHtml(item.image);
                            html += `<div class="cart-item"><img src="imgs/${img}" class="item-img"><div class="item-details"><h4 class="item-name">${name}</h4><span class="text-muted small">${type}</span></div><button type="button" class="btn-remove" onclick="removeFromCart('${escapeHtml(item.id)}')"><i class="fa fa-trash"></i></button></div>`;
                        });
                        container.innerHTML = html; updateCartCounter();
                    }
                    function removeFromCart(id) { let c = JSON.parse(localStorage.getItem('mt_cart_v1')||'[]'); c=c.filter(x=>x.id!==id); localStorage.setItem('mt_cart_v1',JSON.stringify(c)); renderCartItems(); }
                    document.addEventListener('DOMContentLoaded', renderCartItems);
                </script>
            <?php endif; ?>
        </div>
    </div>
    <?php include "footer_site.php"; ?>
</body>
</html>
