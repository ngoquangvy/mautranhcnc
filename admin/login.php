<?php
session_start();
// if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
//     header("location: ../home/");
//     // exit;
// }
// Include config file
require_once "../includes/connectdb.php";


//... The Captcha is valid you can continue with the rest of your code
//... Add code to filter access using $response . score



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['login'];
    $password = $_POST['password'];
  //   $options = [
  //     'cost' => 22,
  // ];
  
  // var_dump($hashed_password);
  // print_r($hashed_password);
      $sql_email = 'SELECT *
      FROM admin
      WHERE username = "' . $username . '"';

      $result = $link->query($sql_email);
      // echo mysqli_num_rows($result);
      if (mysqli_num_rows($result) >0) {
          $row = mysqli_fetch_assoc($result);
          $time_stamp=$row["time_stamp"];
          $t=$row["times"];
           $date = date('Y-m-d');
          $dateTimestamp1 = strtotime($date);
          $dateTimestamp2 = strtotime($time_stamp);
          if ($dateTimestamp1 == $dateTimestamp2) {
            $sql='UPDATE admin SET times = times + 1 WHERE username = "'. $username .'"';
            $result1 = $link->query($sql);
          }else{
            $t=1;
            $sql='UPDATE admin SET times = 0 WHERE username = "'. $username .'"';
            $result1 = $link->query($sql);
          }

    if($t>4){
    echo '<script language="javascript">';
    echo 'alert("Try again after 24 hours")';
    echo '</script>';
    echo '<script language="javascript">';
    echo 'window.location.href = "../admin/"';
    echo '</script>';
    }else{
      
          $sql_email = 'SELECT *
    FROM admin
    WHERE username = "' . $username . '"';

    $result = $link->query($sql_email);
    // echo mysqli_num_rows($result);
    if (mysqli_num_rows($result) >0) {
        $row = mysqli_fetch_assoc($result);
          if(password_verify($password ,$row["password"])) {
            // $_SESSION["loggedin"] = true;
            $sql='UPDATE admin SET times = 0 WHERE username = "'. $username .'"';
            $result1 = $link->query($sql);
            $_SESSION["id"] = $row["username"];
            echo '<script language="javascript">';
            // echo 'alert("Login successful.")';
            echo 'alert("Login successful\nWelcome back '. $_SESSION["username"].'")';
            echo '</script>';
            echo '<script language="javascript">';
            echo 'window.location.href = "../admin/"';
            echo '</script>';
          }
     else {
      
      $sql='UPDATE admin SET time_stamp = now()  WHERE username = "'. $username .'"';
      $result1 = $link->query($sql);
        echo '<script language="javascript">';
        echo 'alert("Email or password is incorrect.\nYou will return Login")';
        echo '</script>';
        echo '<script language="javascript">';
        echo 'window.location.href = "../admin/"';
        echo '</script>';
    }
  }
  else {
    echo '<script language="javascript">';
    echo 'alert("Email or password is incorrect.\nYou will return Login")';
    echo '</script>';
    echo '<script language="javascript">';
    echo 'window.location.href = "../admin/"';
    echo '</script>';
  }

      //end captcha
    
}}}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>ADMIN Login</title>
  <link rel="shortcut icon" href="imgs/logo/logomt.jpg">
  <script src="../home/js/jquery.js"></script>
  <link href="../home/css/bootstrap.min.css" rel="stylesheet">
  <script src="../home/js/bootstrap.min.js"></script>
	<link rel="stylesheet" href="../home/css/login.css">
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  
</head>
<body>
<!------ Include the above in your HEAD tag ---------->

<div class="wrapper fadeInDown">
  <div id="formContent">
    <!-- Tabs Titles -->

    <!-- Icon -->
    <div class="fadeIn first">
     <h1>ADMIN</h1>

    </div>

    <!-- Login Form -->
    <form method="POST" id="form_id" action="login.php">
      <input type="text" id="login" class="fadeIn second" name="login" placeholder="username">
      <input type="password" id="password" class="fadeIn third" name="password" placeholder="password">
          <div>
          <button type="submit"  id="continue" class="fadeIn fourth btn btn-primary" value="Log In">Login</button>
        </div>
      
    </form>

    <!-- Remind Passowrd -->
    <div id="formFooter">
      <a class="underlineHover" href="#">Forgot Password?</a>
    </div>

  </div>
</div>
</body>
</html>