<?php
require_once "../includes/connectdb.php";
session_start();
if (isset($_SESSION['id'])) { 
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $oldpass = $_POST["passold"];
        $newpass = $_POST["passnew"];
            $sql = 'SELECT * FROM admin WHERE username="'. $_SESSION["id"] .'"';
            $result = $link->query($sql);
            $row = mysqli_fetch_assoc($result);
            if(password_verify($oldpass , $row["password"])) {
                $hashed_password = password_hash($newpass, PASSWORD_DEFAULT);
                $sql2 = 'UPDATE admin SET password ="' . $hashed_password . '"  WHERE username="' . $_SESSION["id"] . '"';
                if ($link->query($sql2) === TRUE) {
                    echo '<script language="javascript">';
                    echo 'alert("Success!!")';
                    echo '</script>';
                    echo '<script language="javascript">';
                    echo 'window.location.href = "../admin"';
                    echo '</script>';
                } else {
                    echo '<script language="javascript">';
                    echo 'alert("err!!")';
                    echo '</script>';
                    echo '<script language="javascript">';
                    echo 'window.location.href = "../admin/changepass.php"';
                    echo '</script>';
                }
            
            } else {
               

                echo '<script language="javascript">';
                echo 'alert("Password is wrong!!")';
                echo '</script>';
                echo '<script language="javascript">';
                echo 'window.location.href = "../admin/changepass.php"';
                echo '</script>';
            }
        }

} else {
	echo '<script language="javascript">';
	echo 'alert("Website not available.\nYou will return Login, right now.")';
	echo '</script>';
	echo '<script language="javascript">';
	echo 'window.location.href = "../admin/"';
	echo '</script>';



}
$link->close();
?>