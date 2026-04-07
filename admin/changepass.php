<?php
require_once "../includes/connectdb.php";
require_once "../includes/config_site.php";
if (!defined('SITE_LOGO_PREFIX')) define('SITE_LOGO_PREFIX', '../home/');

if (!isset($_SESSION["id"])) {
    header("location: ../admin");
    exit;
}

// Sidebar logic handled by include
require_once "../includes/config_site.php";
if (!defined('SITE_LOGO_PREFIX')) define('SITE_LOGO_PREFIX', '../home/');

// Get total count for stats
$total_res = $link->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc();
$total_records = $total_res['total'];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu | <?php echo SITE_NAME; ?> Admin</title>
    <link rel="shortcut icon" href="<?php echo SITE_LOGO_PREFIX . SITE_LOGO; ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="../home/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/admin.css?v=1.6" rel="stylesheet">
    <script src="../home/js/jquery.js"></script>
    <script src="../home/js/bootstrap.min.js"></script>
    <script>
        function checkPasswords() {
            var x = document.getElementById("password2");
            var y = document.getElementById("password1");
            var m = document.getElementById("message");
            if (x.value !== "" && y.value !== "") {
                if (x.value != y.value) {
                    m.innerHTML = '<span class="text-danger"><i class="fa fa-times-circle"></i> Mật khẩu không trùng khớp!</span>';
                    document.getElementById("btncp").disabled = true;
                } else {
                    m.innerHTML = '<span class="text-success"><i class="fa fa-check-circle"></i> Mật khẩu trùng khớp</span>';
                    document.getElementById("btncp").disabled = false;
                }
            } else {
                m.innerHTML = "";
            }
        }
    </script>
    <style>
        .password-form-card {
            max-width: 500px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--secondary-slate);
        }

        #btncp {
            width: 100%;
            padding: 12px;
            font-weight: 700;
            border-radius: 10px;
            margin-top: 20px;
            transition: all 0.3s;
        }
    </style>
</head>

<body>

    <?php include "../includes/sidebar_admin.php"; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <div>
                <h1 style="font-weight: 800; font-size: 32px; letter-spacing: -1.5px; margin: 0;"><?php echo mb_strtoupper(SITE_NAME, 'UTF-8'); ?></h1>
                <p class="text-muted" style="font-weight: 500;">Bảo mật tài khoản & Cập nhật mật khẩu quản trị</p>
            </div>
        </header>

        <div class="password-form-card fadeInDown">
            <div class="text-center mb-4">
                <div class="stat-icon m-auto mb-3"
                    style="width: 70px; height: 70px; background: rgba(52, 152, 219, 0.1);">
                    <i class="fa fa-lock fa-2x"></i>
                </div>
                <h3 style="font-weight: 700;">Đổi mật khẩu</h3>
                <p class="text-muted" style="font-size: 14px;">Nhập thông tin bên dưới để cập nhật</p>
            </div>

            <form method="POST" action="../admin/pass.php">
                <input type="hidden" name="csrf_token" value="<?php echo Security\generate_csrf_token(); ?>">
                <div class="form-group mb-3">
                    <label>Mật khẩu cũ</label>
                    <input type="password" id="passwordold" class="form-control" name="passold"
                        placeholder="Nhập mật khẩu hiện tại" required>
                </div>

                <div class="form-group mb-3">
                    <label>Mật khẩu mới</label>
                    <input type="password" id="password1" class="form-control" name="passnew"
                        placeholder="Nhập mật khẩu mới" onkeyup="checkPasswords()" required>
                </div>

                <div class="form-group mb-2">
                    <label>Xác nhận mật khẩu</label>
                    <input type="password" id="password2" class="form-control" name="passnew2"
                        placeholder="Nhập lại mật khẩu mới" onkeyup="checkPasswords()" required>
                </div>

                <div id='message' class="mb-3" style="height: 20px; font-size: 13px; font-weight: 500;"></div>

                <button type="submit" id="btncp" class="btn btn-primary btn-lg">
                    <i class="fa fa-save mr-2"></i> CẬP NHẬT NGAY
                </button>
            </form>
        </div>
    </main>

    <script>
        // TRUYỀN CSRF TOKEN TỪ PHP SANG JAVASCRIPT
        window.CSRF_TOKEN = "<?php echo Security\generate_csrf_token(); ?>";
    </script>
    <script src="../home/js/my.js"></script>
</body>

</html>