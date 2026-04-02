<?php
// if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
//     header("location: ../home/");
//     // exit;
// }
// Include config file
require_once "../includes/connectdb.php";


// -------------------------------------------------------------
// GIẢI THÍCH BẢO MẬT: ENV-BASED CONFIG
// -------------------------------------------------------------
// Khóa SECRET hiện đã được nạp từ biến môi trường qua connectdb.php. 
// Việc này giúp bảo vệ hệ thống tuyệt đối khi bạn chia sẻ mã nguồn 
// cho người khác hoặc đẩy lên Git.
$recaptcha_secret = RECAPTCHA_SECRET_KEY;
// -------------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. XÁC THỰC RECAPTCHA (CHỐNG BOT CẤP ĐỘ CAO)
    // -------------------------------------------------------------
    $captcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    if (Security\is_recaptcha_enabled() && empty($captcha_response)) {
        die("BÁO LỖI: Bạn chưa tích vào ô reCAPTCHA (Tôi không phải là người máy)!");
    }

    if (Security\is_recaptcha_enabled()) {
        // -------------------------------------------------------------
        // GIẢI THÍCH BẢO MẬT: SỬ DỤNG HÀM XÁC THỰC TẬP TRUNG
        // -------------------------------------------------------------
        // Thay vì tự viết mã xác thực (dễ sai sót SSL), chúng ta gọi 
        // hàm verify_recaptcha() từ Security library đã được bật xác thực SSL.
        if (!Security\verify_recaptcha($captcha_response)) {
            die("BÁO LỖI: reCAPTCHA thất bại! Vui lòng thử lại hoặc liên hệ kỹ thuật.");
        }
    }
    // -------------------------------------------------------------

    // 2. KIỂM TRA CSRF TOKEN
    if (!isset($_POST['csrf_token']) || !Security\verify_csrf_token($_POST['csrf_token'])) {
        die("Lỗi bảo mật: CSRF Token không hợp lệ!");
    }
    // -------------------------------------------------------------

    $username = $_POST['login'];
    $password = $_POST['password'];

    /* 
    // MÃ NGUỒN CŨ (DỄ BỊ TẤN CÔNG SQL INJECTION)
    $sql_email = 'SELECT * FROM admin WHERE username = "' . $username . '"';
    $result = $link->query($sql_email);
    */

    // MÃ NGUỒN MỚI: SỬ DỤNG PREPARED STATEMENTS
    $stmt = $link->prepare("SELECT username, password, times, time_stamp FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $time_stamp = $row["time_stamp"];
        $t = $row["times"];
        $date = date('Y-m-d');
        
        if ($date == $time_stamp) {
            /* 
            // CŨ
            $sql='UPDATE admin SET times = times + 1 WHERE username = "'. $username .'"';
            $result1 = $link->query($sql);
            */
            // MỚI
            $stmt_up = $link->prepare("UPDATE admin SET times = times + 1 WHERE username = ?");
            $stmt_up->bind_param("s", $username);
            $stmt_up->execute();
        } else {
            $t = 1;
            /*
            // CŨ
            $sql='UPDATE admin SET times = 0 WHERE username = "'. $username .'"';
            $result1 = $link->query($sql);
            */
            // MỚI
            $stmt_reset = $link->prepare("UPDATE admin SET times = 0 WHERE username = ?");
            $stmt_reset->bind_param("s", $username);
            $stmt_reset->execute();
        }

        if ($t > 4) {
            echo '<script language="javascript">alert("Try again after 24 hours"); window.location.href = "../admin/";</script>';
            exit;
        } else {
            if (password_verify($password, $row["password"])) {
                // CHỐNG SESSION FIXATION
                session_regenerate_id(true);

                // RESET LOGIN ATTEMPTS
                $stmt_clear = $link->prepare("UPDATE admin SET times = 0 WHERE username = ?");
                $stmt_clear->bind_param("s", $username);
                $stmt_clear->execute();

                $_SESSION["id"] = $row["username"];
                
                echo '<script language="javascript">alert("Login successful\nWelcome back '. htmlspecialchars($username) .'"); window.location.href = "../admin/";</script>';
                exit;
            } else {
                // -------------------------------------------------------------
                // GIẢI THÍCH BẢO MẬT: SECURITY BACKOFF (ANTI-BRUTE FORCE)
                // -------------------------------------------------------------
                // LỖI (VÁ): Đã di chuyển sleep(2) vào khối SAI MẬT KHẨU. 
                // Bot giờ đây sẽ bị "treo" 2 giây cho mỗi lần đoán sai.
                sleep(2);
                echo '<script language="javascript">alert("Email or password is incorrect."); window.location.href = "../admin/";</script>';
                exit;
            }
        }
    } else {
        // GIẢI THÍCH BẢO MẬT: TIMING ATTACK PROTECTION
        // Bắt bot đợi ngay cả khi USERNAME KHÔNG TỒN TẠI để chúng không 
        // biết được tài khoản này có thật trên hệ thống hay không.
        sleep(2);
        echo '<script language="javascript">alert("Email or password is incorrect."); window.location.href = "../admin/";</script>';
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>ADMIN Login</title>
  <link rel="shortcut icon" href="imgs/logo/logomt.jpg">
  <script src="../home/js/jquery.js"></script>
  <link href="../home/css/bootstrap.min.css" rel="stylesheet">
  <script src="../home/js/bootstrap.min.js"></script>
	<link rel="stylesheet" href="../home/css/login.css">
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  
</head>
<body>
<!------ Include the above in your HEAD tag ---------->

<div class="wrapper fadeInDown">
  <div id="formContent">
    <!-- Tabs Titles -->

    <!-- Icon -->
    <div class="fadeIn first">
     <h1>ADMIN</h1>

    </div>

    <!-- Login Form -->
    <form method="POST" id="form_id" action="login.php">
      <input type="hidden" name="csrf_token" value="<?php echo Security\generate_csrf_token(); ?>">
      <input type="text" id="login" class="fadeIn second" name="login" placeholder="username">
      <input type="password" id="password" class="fadeIn third" name="password" placeholder="password">
      
      <!-- GOOGLE RECAPTCHA WIDGET (SITE KEY FROM ENV) -->
      <?php if (Security\is_recaptcha_enabled()): ?>
      <center>
        <div class="g-recaptcha fadeIn" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
      </center>
      <?php endif; ?>
      
      <div>
          <button type="submit"  id="continue" class="fadeIn fourth btn btn-primary" value="Log In">Login</button>
        </div>
      
    </form>

    <!-- Remind Passowrd -->
    <div id="formFooter">
      <a class="underlineHover" href="#">Forgot Password?</a>
    </div>

  </div>
</div>
</body>
</html>
