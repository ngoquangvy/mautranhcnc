<?php
session_start();
require_once "../includes/connectdb.php";

if(isset($_SESSION["id"])){
    header("location: ../admin/admin.php");
}
else{
    header("location: ../admin/login.php");
}

?>