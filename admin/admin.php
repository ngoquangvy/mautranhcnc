<?php

session_start();

require_once "../includes/connectdb.php";



if (!isset($_SESSION["id"])) {

  header("location: ../admin");
}

$show_product = "";

$sql_fr1 = "SELECT * from products order by id DESC";



$result_fr1 = $link->query($sql_fr1);

if ($result_fr1 && ($result_fr1->num_rows > 0)) {

  while ($row_fr1 = mysqli_fetch_assoc($result_fr1)) {

    // $rf=$row_fr1["id"];

    // print_r($row_fr1);

    $show_product = $show_product . '<div class="col-sm-3" id="' . $row_fr1["prourl"] . '">

              <div class="imgre">

                <img class="img-fluid" src="../home/imgs/' . $row_fr1["prourl"] . '" alt="">

                </div>

                <div  class="description">

                  <h5>' . $row_fr1["proname"] . '<button id="' . $row_fr1["prourl"] . 'abc" type="button"onClick="deleteproduct(this.value)" class="bg-danger text-white btndel" value="' . $row_fr1["prourl"] . '">DELETE</button></h5>

                  <h7>' . $row_fr1["protype"] . '</h7>

                  <p>' . $row_fr1["description"] . '</p>

                </div>

          </div>';
  }
}

$show_protype = "";

$sql_fr1 = "SELECT * from products group by protype";

$result_fr1 = $link->query($sql_fr1);

if (
  $result_fr1 && ($result_fr1->num_rows > 0)

) {

  while ($row_fr1 = mysqli_fetch_assoc($result_fr1)) {

    // $rf=$row_fr1["id"];

    $show_protype = $show_protype . ' 

                <li class="nav-item">
                  <p class="nav-link type"  value="' . $row_fr1["protype"] . '" > ' . $row_fr1["protype"] . ' <button type="button" class="bg-danger text-white btndel" value="' . $row_fr1["protype"] . '">DELETE</button></p>
              </li>';
  }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

  <meta charset="UTF-8">

  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Dashboard</title>

  <link rel="shortcut icon" href="imgs/logo/logomt.jpg">

  <script src="../home/js/jquery.js"></script>

  <link href="../home/css/bootstrap.min.css" rel="stylesheet">

  <link href="../home/css/my.css" rel="stylesheet">

  <script src="../home/js/bootstrap.min.js"></script>

  <script src="../home/js/my.js"></script>

</head>

<body>

  <body class="bg-light">

    <header class="headerr">

      <nav class="navbar navbar-expand-md navbar-dark bg-dark d-flex" id="navbar">

        <div class="container">

          <a class="navbar-brand" onclick="location.href='../admin';" class="text-white">Mẫu CNC</a>

          <button class="navbar-toggler " type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">

            <span class="navbar-toggler-icon"></span>

          </button>

          <div class="collapse navbar-collapse" id="navbarsExampleDefault">

            <ul class="navbar-nav mr-auto">

              <li class="nav-item active">

                <a class="nav-link" href="#" onclick="location.href='../admin';">Home</a>

              </li>

              <li class="nav-item">

                <a class="nav-link" href="#" onclick="location.href='../admin/changepass.php';">CHANGE PASSWORD</a>

              </li>

              <li class="nav-item">

                <a class="nav-link" onclick="location.href='../admin/addproduct.php';">ADD Products</a>

              </li>



            </ul>

          </div>

          <div class="w-25">

            <input type="text" class="form-control rounded-0 bg-dark ser-input" id="ser-input" placeholder="Search...">

          </div>

        </div>

        <div>

          <a class="nav-link" onclick="location.href='../admin/logout.php';" href="#">Logout</a>

        </div>

      </nav>

      <nav class="navbar-dark bg-dark d-flex" id="navbar">

        <div class="container">

          <a class="navbar-brand" href="#" class="text-white">Loại Mẫu:</a>

          <button class="navbar-toggler " type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">

            <span class="navbar-toggler-icon"></span>

          </button>

          <div class="collapse navbar-collapse" id="navbarsExampleDefault">

            <ul class="scrollnav">

              <?php echo $show_protype ?>

            </ul>

          </div>

        </div>

      </nav>

    </header>





    <!-- end slide -->

    <div class="listcnc">

      <div class="container">

        <div class="row" id="products">

          <!-- <div class="col-sm-3">

                  <div class="imgre">

                    <img class="img-fluid" src="imgs/a (1).jpg" alt="">

                    </div>

                    <div  class="description">

                      <h5>Mẫu 1</h5>

                      <h7>Loại: 3D</h7>

                      <p>Cross-platform PowerShell</p>

                    </div>

              </div> -->

          <?php echo $show_product ?>



        </div>

      </div>

  </body>

</html>