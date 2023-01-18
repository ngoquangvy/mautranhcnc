<?php
session_start();
if (isset($_SESSION['id'])) { } else {
	echo '<script language="javascript">';
	echo 'alert("Website not available.\nYou will return Home, right now.")';
	echo '</script>';
	echo '<script language="javascript">';
	echo 'window.location.href = "../admin/"';
	echo '</script>';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>ADMIN CHANGEPASSWORD</title>
  <script src="../home/js/jquery.js"></script>
  <link href="../home/css/bootstrap.min.css" rel="stylesheet">
  <script src="../home/js/bootstrap.min.js"></script>
	<link rel="stylesheet" href="../home//css/login.css">
</head>
<script>

function myFunction() {
  var x = document.getElementById("password2");
  var y = document.getElementById("password1");
  var m = document.getElementById("message");
  if(x.value != y.value ){
    m.innerHTML="Password are not matching!";
      document.getElementById("btncp").disabled = true;  
  }
  else{
    m.innerHTML="Match";
    document.getElementById("btncp").disabled = false;
  }
  }

</script>
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
    <form method="POST" action="../admin/pass.php">
      <input type="password" id="passwordold" class="fadeIn second" name="passold" placeholder="OLD Passowrd">
      <input type="password" id="password1" class="fadeIn third" name="passnew" placeholder="New Passowrd">
      <input type="password" id="password2" class="fadeIn five" onkeyup="myFunction()" name="passnew2" placeholder="Confirm Passowrd">
      <span id='message'></span>
      <input type="submit" id="btncp" class="fadeIn fourth" value="CHANGE PASSWORD">
    </form>

    <!-- Remind Passowrd -->
    <div id="formFooter">
      <a class="underlineHover" href="#">Forgot Password?</a>
    </div>

  </div>
</div>
	
</body>
</html>