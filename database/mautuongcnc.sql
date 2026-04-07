-- ╔══════════════════════════════════════════════════════════════╗
-- ║              MẪU TƯỢNG GIÁ RẺ — Database Schema            ║
-- ╠══════════════════════════════════════════════════════════════╣
-- ║  Phiên bản: 2.0                                             ║
-- ║  Engine: InnoDB | Charset: utf8mb4                          ║
-- ║  Múi giờ: +07:00 (Việt Nam) — đặt trong connectdb.php      ║
-- ║                                                              ║
-- ║  CÁCH SỬ DỤNG:                                               ║
-- ║  1. Tạo database mới (hoặc dùng database có sẵn)            ║
-- ║  2. Import file này vào phpMyAdmin hoặc chạy:                ║
-- ║     mysql -u root -p <tên_database> < mautuongcnc.sql       ║
-- ║  3. Cấu hình tên database trong file .env (xem .env.example)║
-- ║                                                              ║
-- ║  LƯU Ý:                                                      ║
-- ║  • File này chỉ chứa CẤU TRÚC (schema), không có dữ liệu   ║
-- ║  • Bạn cần tự tạo tài khoản admin sau khi import             ║
-- ║  • Dùng IF NOT EXISTS → chạy nhiều lần không lỗi             ║
-- ╚══════════════════════════════════════════════════════════════╝

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- ═══════════════════════════════════════════════════════════
-- BẢNG 1: admin
-- Quản lý tài khoản đăng nhập Admin Dashboard
-- ═══════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `admin` (
  `username` VARCHAR(20) NOT NULL COMMENT 'Tên đăng nhập',
  `password` VARCHAR(100) NOT NULL COMMENT 'Mật khẩu đã mã hóa (bcrypt)',
  `times` INT NOT NULL DEFAULT 0 COMMENT 'Số lần đăng nhập thất bại',
  `time_stamp` DATE NOT NULL COMMENT 'Ngày đăng nhập gần nhất',
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tài khoản quản trị viên';

-- ═══════════════════════════════════════════════════════════
-- BẢNG 2: products
-- Danh sách sản phẩm (ảnh mẫu tượng)
-- ═══════════════════════════════════════════════════════════
-- prourl = tên file ảnh trong thư mục /home/imgs/
-- protype = tên danh mục (dùng để nhóm sản phẩm trên Sidebar)
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'ID sản phẩm (tự tăng)',
  `proname` VARCHAR(255) NOT NULL COMMENT 'Tên hiển thị sản phẩm',
  `protype` VARCHAR(100) DEFAULT NULL COMMENT 'Tên danh mục (nhóm)',
  `prourl` VARCHAR(255) NOT NULL COMMENT 'Tên file ảnh (VD: 1734620331.jpg)',
  `description` TEXT DEFAULT NULL COMMENT 'Mô tả chi tiết sản phẩm',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm thêm sản phẩm',
  PRIMARY KEY (`id`),
  KEY `idx_products_protype` (`protype`),
  KEY `idx_products_prourl` (`prourl`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Danh sách sản phẩm mẫu tượng';

-- ═══════════════════════════════════════════════════════════
-- BẢNG 3: orders
-- Quản lý đơn đặt hàng từ khách hàng (cart.php)
-- ═══════════════════════════════════════════════════════════
-- Trạng thái: new → processed → trashed
-- is_suspicious: đánh dấu đơn nghi bot spam (honeypot/time trap)
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'ID đơn hàng (tự tăng)',
  `customer_name` VARCHAR(100) NOT NULL COMMENT 'Tên khách hàng',
  `customer_phone` VARCHAR(100) NOT NULL COMMENT 'Số điện thoại',
  `note` TEXT DEFAULT NULL COMMENT 'Ghi chú / Yêu cầu đặc biệt',
  `status` ENUM('new','processed','trashed') NOT NULL DEFAULT 'new' COMMENT 'Trạng thái đơn hàng',
  `is_suspicious` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Nghi ngờ bot (0=bình thường, 1=nghi ngờ)',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm đặt hàng',
  `processed_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm Admin xử lý',
  `trashed_at` DATETIME DEFAULT NULL COMMENT 'Thời điểm Admin hủy đơn',
  PRIMARY KEY (`id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Đơn đặt hàng từ khách hàng';

-- ═══════════════════════════════════════════════════════════
-- BẢNG 4: order_items
-- Chi tiết sản phẩm trong mỗi đơn hàng (1 đơn → nhiều sản phẩm)
-- ═══════════════════════════════════════════════════════════
-- Khi xóa đơn hàng → Tự động xóa order_items (ON DELETE CASCADE)
-- product_name lưu riêng để giữ tên khi sản phẩm bị xóa khỏi bảng products
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT 'ID chi tiết (tự tăng)',
  `order_id` INT NOT NULL COMMENT 'ID đơn hàng (FK → orders.id)',
  `product_id` INT NOT NULL COMMENT 'ID sản phẩm (FK → products.id)',
  `product_name` VARCHAR(255) NOT NULL COMMENT 'Tên sản phẩm tại thời điểm đặt',
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order_id` (`order_id`),
  KEY `idx_order_items_product_id` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`)
    REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Chi tiết sản phẩm trong mỗi đơn hàng';
