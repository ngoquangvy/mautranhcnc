<?php
session_start();
// This script is a temporary bypass for the admin password.
// Use it once to log in, then delete it.
$_SESSION["id"] = "admin";
header("location: admin.php");
?>
