<?php
require_once "../includes/connectdb.php";
session_start();
if (isset($_SESSION['id'])) { 
$id = $_GET['t'];
$sql='DELETE FROM products WHERE  prourl = "' . $id . '" '; 
unlink('../home/imgs/' . $id . '');
$result = $link->query($sql);
$show_product="";
$sql_fr1='SELECT * from products ORDER BY id DESC '; 
$result_fr1 = $link->query($sql_fr1);

$show_product = '<script language="javascript">window.location.href = "../admin/";</script> 
                    <script language="javascript">alert("Deleted!")</script>';
echo $show_product;
}
else
{
    echo "";
}
?>