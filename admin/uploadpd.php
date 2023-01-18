<?php
session_start();
require_once "../includes/connectdb.php";

// print_r($_POST);
extract($_POST);
$error = array();
$f = "../home/imgs/";
$extension = array("jpeg","jpg","png","gif");
//print_r($_FILES["images"]);
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_SESSION["id"])) {
        $des = $_POST['desimg'];
        $proname = $_POST["nameimg"];
        $protype = $_POST["typeimg"];

        foreach($_FILES["images"]["tmp_name"] as $key=>$tmp_name) {
            $file_name = $_FILES['images']['name'][$key];
            $file_tmp = $_FILES['images']['tmp_name'][$key];

            //TODO:  The strtolower() function converts a string to lowercase
            $new_file_name = strtolower($file_name);
            // echo $new_file_name;
            //TODO:  The str_replace() function replaces some characters with some other characters in a string
            $final_file = str_replace(' ', '-',$new_file_name);

            $final_file = time().rand(1000,9999).".jpg";


            // $ext=pathinfo($file_name,PATHINFO_EXTENSION);
            
            // if(in_array($ext,$extension)) {
                
            if (move_uploaded_file($file_tmp, $f . $final_file)) {

                        $sql_iphoto = 'INSERT INTO products(proname, protype, prourl, description) VALUES ("'.$proname.'","'.$protype.'","'.$final_file.'","'.$des.'")';
                        mysqli_query($link, $sql_iphoto);
                        echo '<script language="javascript">';
                        echo 'alert("Upload successfully")';
                        echo '</script>';
                        echo '<script language="javascript">';
                        echo 'window.location.href = "../admin/addproduct.php"';
                        echo '</script>';
                    }
            else {
                    array_push($error,"$file_name, ");
                }
            // }
        }
        
    }
 }
 else {
    echo "Nothing here";
}
mysqli_close($link);