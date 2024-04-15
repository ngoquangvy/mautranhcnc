<?php

session_start();
require_once "../includes/connectdb.php";

// Validate session
if (!isset($_SESSION["id"])) {
    echo "Unauthorized access";
    exit;
}

$f = "../home/imgs/";
$extension = array("jpeg", "jpg", "png", "gif");
$error = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $des = $_POST['desimg'];
    $pronamere = $_POST["nameimg"];
    $protype = $_POST["typeimg"];
    // Prepare the SQL statement with a WHERE clause
    $stmtt = $link->prepare('SELECT COUNT(DISTINCT id) AS total FROM products WHERE protype ="' . $protype . '"');
    $stmtt->execute();
    $result = $stmtt->get_result();
    $row = $result->fetch_assoc();
    $total = $row['total']; // total number of records

    // Bind result variables
    if ($total == 0) $total++;
    $countitems = $total;
    // Loop through each uploaded file
    foreach ($_FILES["images"]["tmp_name"] as $key => $tmp_name) {
        $countitems++;
        $proname = $pronamere . $countitems;
        $file_name = $_FILES['images']['name'][$key];
        $file_tmp = $_FILES['images']['tmp_name'][$key];

        // Generate a unique file name
        $final_file = time() . rand(1000, 9999) . ".jpg";

        // Upload file to target directory
        $target_file = $f . $final_file;

        if (move_uploaded_file($file_tmp, $target_file)) {
            // File uploaded successfully, proceed to insert into database
            $sql_iphoto = 'INSERT INTO products(proname, protype, prourl, description) VALUES (?, ?, ?, ?)';

            $stmt = mysqli_prepare($link, $sql_iphoto);

            if ($stmt) {
                // Bind parameters to the prepared statement
                mysqli_stmt_bind_param($stmt, 'ssss', $proname, $protype, $final_file, $des);

                // Execute the prepared statement
                $result = mysqli_stmt_execute($stmt);

                if ($result) {
                    // Insertion successful
                    echo '<script>alert("Upload successful");</script>';
                    echo '<script>window.location.href = "../admin/addproduct.php";</script>';
                } else {
                    // Insertion failed
                    $error_message = "Failed to insert product into database: " . mysqli_error($link);
                    array_push($error, $error_message);
                }

                // Close the prepared statement
                mysqli_stmt_close($stmt);
            } else {
                // Error in preparing the statement
                $error_message = "Error: Unable to prepare statement";
                array_push($error, $error_message);
            }
        } else {
            // File upload failed
            $error_message = "Error: Failed to upload file";
            array_push($error, $error_message);
        }
    }

    // Output errors if any occurred
    if (!empty($error)) {
        echo '<script>alert("Errors occurred: ' . implode(', ', $error) . '");</script>';
    }
} else {
    echo "Invalid request";
}

mysqli_close($link);
