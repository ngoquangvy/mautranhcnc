<?php
if (!defined('MT_CNC_AUTH'))
    exit('Access Denied');
/**
 * Website Global Configuration
 * Centralized settings for contact information and site identity.
 * Edit this file to update info across the entire website.
 */

// Site Identity
if (!defined('SITE_NAME'))
    define('SITE_NAME', 'Mẫu tượng giá rẻ');
if (!defined('SITE_LOGO'))
    define('SITE_LOGO', 'imgs/logo/mt_logo.png');
if (!defined('SITE_FAVICON'))
    define('SITE_FAVICON', 'imgs/logo/mt_logo.png');

// --- Centralized SEO & Social Metadata ---
// Edit these constants once to update the entire website
if (!defined('SITE_DESCRIPTION'))
    define('SITE_DESCRIPTION', 'Chuyên cung cấp các mẫu tượng giá rẻ, mẫu tượng Phật giáo, Công giáo và điêu khắc gỗ nghệ thuật tinh xảo.');
if (!defined('SITE_KEYWORDS'))
    define('SITE_KEYWORDS', 'mẫu tượng giá rẻ, tượng gỗ đẹp, điêu khắc gỗ, mẫu tượng phật, mẫu tượng công giáo');
if (!defined('OG_IMAGE_URL'))
    define('OG_IMAGE_URL', 'imgs/logo/mt_logo.png'); // Default image when sharing links

// --- List of Fun Slogans for Marquee (Chạy chữ đầu trang) ---
if (!defined('SITE_SLOGANS')) {
    define('SITE_SLOGANS', [
        "🔥 Giao hàng toàn Thái Dương Hệ - File đến sau một nén nhang",
        "🏆 Mẫu chuẩn thợ - Chạy bao mượt, bao... phê",
        "🚀 Dùng đến đời... cháu vẫn tốt",
        "🛡️ Bảo hành trọn đời - Đến khi bạn... chán nghề thì thôi!",
        "✨ Thiên biến vạn hóa - Ý tưởng của bạn, tuyệt phẩm của tôi!",
        "💎 Đúc mẫu theo ý - Rèn file theo tâm - Đẹp không tì vết",
        "🎨 Vẽ mộng cho đời - Hiện thực hóa mọi ý tưởng điên rồ nhất"
    ]);
}
// -----------------------------------------------------------

// Contact Information
if (!defined('CONTACT_MANAGER'))
    define('CONTACT_MANAGER', 'Duyên');
if (!defined('CONTACT_PHONE'))
    define('CONTACT_PHONE', '0374310831');
if (!defined('CONTACT_ZALO'))
    define('CONTACT_ZALO', '0374310831');
if (!defined('CONTACT_GMAIL'))
    define('CONTACT_GMAIL', 'buiminhthien96@gmail.com');

// Social & Contact Links
if (!defined('URL_FACEBOOK'))
    define('URL_FACEBOOK', 'https://www.facebook.com/myduyen.nguyen.1293575');
if (!defined('URL_ZALO'))
    define('URL_ZALO', 'https://zalo.me/0374310831');

// -------------------------------------------------------------
// WATERMARK SETTINGS — Chỉnh tại đây!
// -------------------------------------------------------------
if (!defined('WM_TEXT'))
    define('WM_TEXT', 'Mautuonggiare.com');
if (!defined('WM_LOGO_RELATIVE_PATH'))
    define('WM_LOGO_RELATIVE_PATH', '../home/imgs/logo/mt_logo.png');

if (!defined('WM_SHOW_LOGO'))
    define('WM_SHOW_LOGO', false);     // Hiện logo MT
if (!defined('WM_SHOW_TEXT'))
    define('WM_SHOW_TEXT', true);      // Hiện chữ WM_TEXT
if (!defined('WM_SHOW_CIRCLE'))
    define('WM_SHOW_CIRCLE', true);   // Hiện nền tròn trắng phía sau logo

if (!defined('WM_LOGO_SIZE'))
    define('WM_LOGO_SIZE', 0.35);      // Logo: tỉ lệ theo cạnh ngắn ảnh (0.0 – 1.0)
if (!defined('WM_TEXT_SIZE'))
    define('WM_TEXT_SIZE', 0.08);      // Chữ: tỉ lệ chiều cao cạnh ngắn (0.0 – 0.2)

if (!defined('WM_LOGO_OPACITY'))
    define('WM_LOGO_OPACITY', 20);     // Logo: 0 = ẩn → 100 = hiện rõ
if (!defined('WM_TEXT_ALPHA'))
    define('WM_TEXT_ALPHA', 100);      // Chữ: 0 = rõ → 127 = ẩn hoàn toàn
if (!defined('WM_CIRCLE_ALPHA'))
    define('WM_CIRCLE_ALPHA', 115);    // Nền tròn: 0 = đặc → 127 = ẩn
?>