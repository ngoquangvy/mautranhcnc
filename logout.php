<?php
// Initialize the session
session_start();

unset($_SESSION["id"]);
//session_destroy();

// Redirect to login page
header("location: login/");
exit;
?>