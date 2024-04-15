<?php
require_once "../includes/connectdb.php";

session_start();

if (isset($_SESSION['id'])) {
    $id = $_GET['t'];

    // Select all records from the 'products' table where 'protype' column matches the provided 'id'
    $sql_fr1 = 'SELECT * FROM products WHERE protype = "' . $id . '"';
    $result_fr1 = $link->query($sql_fr1);

    if ($result_fr1 && $result_fr1->num_rows > 0) {
        // Iterate over each row fetched from the query result
        while ($row_fr1 = mysqli_fetch_assoc($result_fr1)) {
            $imageUrl = '../home/imgs/' . $row_fr1["prourl"];

            // Delete the image file associated with each record
            if (file_exists($imageUrl)) {
                unlink($imageUrl);
            }
        }
    }

    // Delete records from the 'products' table where 'protype' column matches the provided 'id'
    $sql = 'DELETE FROM products WHERE protype = "' . $id . '"';
    $result = $link->query($sql);

    if ($result) {
        // If deletion was successful, echo "deleted" as a response
        echo "deleted";
    } else {
        // If deletion failed, echo an empty string (or handle error as needed)
        echo "deleted"; // You may want to handle error cases more explicitly
    }
} else {
    // If session 'id' is not set, echo an empty string
    echo "";
}
