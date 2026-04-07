<?php 
// Adjust image path for this subdirectory
if (!defined('SITE_LOGO_PREFIX')) define('SITE_LOGO_PREFIX', '../');
require_once "../../includes/config_site.php"; 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="shortcut icon" href="<?php echo SITE_LOGO_PREFIX . SITE_LOGO; ?>">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="./style.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <script src="./scr.js"></script>
</head>

<body>
      <div class="titlecontent">
    </div>
    <div class="typepro" id="typepro">
    </div>
    <!-- Next and Previous Buttons -->
    <button class="scroll-button slide-button material-symbols-rounded" id="prevButton">chevron_left</button>
    <button class="scroll-button slide-button material-symbols-rounded" id="nextButton">chevron_right</button>
    <?php include "../../includes/footer_site.php"; ?>
</body>

</html>