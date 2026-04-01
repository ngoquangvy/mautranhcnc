<?php
session_start();
require_once "../includes/cache.php";

if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    FileCache::toggle();
}

header("Location: admin.php");
exit;
