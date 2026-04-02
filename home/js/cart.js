/**
 * Giỏ hàng LocalStorage - Mẫu CNC
 * File này quản lý tất cả thao tác giỏ hàng thuần túy trên Client.
 */

const CART_KEY = 'mt_cart_v1';

// Lấy giỏ hàng từ LocalStorage
function getCart() {
    const cartStr = localStorage.getItem(CART_KEY);
    if (!cartStr) return [];
    try {
        return JSON.parse(cartStr);
    } catch (e) {
        console.error("Cart parse error:", e);
        return [];
    }
}

// Lưu giỏ hàng
function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    updateCartCounter();
}

// Cập nhật số lượng trên icon giỏ hàng
function updateCartCounter() {
    const cart = getCart();
    // Tổng số mẫu (chỉ đếm số loại mẫu duy nhất, hoặc đếm tổng item)
    // Dưới đây đếm Tổng Loại mẫu:
    const totalItems = cart.length; 
    
    // Tìm tất cả các badge giỏ hàng trên UI và cập nhật
    const badges = document.querySelectorAll('.cart-badge-count');
    badges.forEach(badge => {
        badge.innerText = totalItems;
        if (totalItems > 0) {
            badge.style.display = 'inline-block';
            // Tạo hiệu ứng nẩy
            badge.style.transform = 'scale(1.5)';
            setTimeout(() => badge.style.transform = 'scale(1)', 200);
        } else {
            badge.style.display = 'none';
        }
    });
}

// Thêm sản phẩm vào giỏ
function addToCart(event, id, name, imageUrl, type) {
    // Ngăn chặn link chuyển trang
    event.preventDefault();
    event.stopPropagation();
    
    let cart = getCart();
    
    // Kiểm tra xem đã có chưa
    const existingIndex = cart.findIndex(item => item.id === id);
    if (existingIndex !== -1) {
        // Đã có rồi, hiển thị thông báo
        showToast(`Mẫu "${name}" đã có trong giỏ hàng!`);
        return false; // hoặc có thể tăng số lượng nếu là SP vật lý
    }
    
    // Thêm mới
    cart.push({
        id: id,
        name: name,
        image: imageUrl,
        type: type,
        addedAt: new Date().toISOString()
    });
    
    saveCart(cart);
    
    // Hiển thị thông báo thành công
    showToast(`Đã thêm "${name}" vào giỏ!`);
    
    // Nhấp nháy icon giỏ hàng
    const cartIcon = document.querySelector('.cart-nav-icon');
    if (cartIcon) {
        cartIcon.style.animation = 'pulse-ring 0.5s ease-out';
        setTimeout(() => cartIcon.style.animation = 'none', 500);
    }
}

// Hệ thống Toast thông báo gọn nhẹ
function showToast(message) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.style.cssText = `
        background: rgba(30, 41, 59, 0.95);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        border-left: 4px solid var(--accent-color, #10b981);
        font-weight: 500;
        font-size: 0.9rem;
        transform: translateX(100%);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    `;
    toast.innerText = message;
    
    container.appendChild(toast);
    
    // Animation In
    requestAnimationFrame(() => {
        toast.style.transform = 'translateX(0)';
        toast.style.opacity = '1';
    });
    
    // Animation Out
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}

// Xóa sản phẩm khỏi giỏ
function removeFromCart(id) {
    let cart = getCart();
    cart = cart.filter(item => item.id !== id);
    saveCart(cart);
    showToast("Đã xóa khỏi giỏ!");
    
    // Nếu đang đứng ở trang cart.php, cần re-render lại bảng
    if (typeof renderCartItems === 'function') {
        renderCartItems();
    }
}

// Khởi chạy hệ thống sau khi trang load xong
document.addEventListener('DOMContentLoaded', () => {
    updateCartCounter();
});
