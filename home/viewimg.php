<?php

require_once "../includes/connectdb.php";



$id = $_GET['id'];

$show_product="";

$sql_fr1='SELECT * from products where id ="' . $id . '"  '; 

$result_fr1 = $link->query($sql_fr1);



if ($result_fr1&&($result_fr1->num_rows > 0)){

    while($row_fr1 = mysqli_fetch_assoc($result_fr1)){


        $show_product = $row_fr1["prourl"];
    }

}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <title>MauCNC</title>
    <style>
body, html {
  height: 100%;
  margin: 0;
}
img {
  padding-top: 10%;
}


  @media only screen and (max-width: 600px) {
  img {
    padding-top: 50%;
  }
}


  /* Center and scale the image nicely */
  
</style>
</head>
<body>
  <img class="img-fluid rounded mx-auto d-block" alt="Responsive image"  src="imgs/<?php echo $show_product ?>" >

</body>
</html>