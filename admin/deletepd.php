<?php

require_once "../includes/connectdb.php";

session_start();

if (isset($_SESSION['id'])) {

    $id = $_GET['t'];

    $sql = 'DELETE FROM products WHERE  prourl = "' . $id . '" ';

    // Delete the image file associated with each record
    if (file_exists($id)) {
        unlink($id);
    }

    $result = $link->query($sql);

    $show_product = 'deleted';

    echo $show_product;
} else {
    echo "";
}
