<?php

require_once "../includes/connectdb.php";



$id = $_GET['id'];

$show_product = "";

$sql_fr1 = 'SELECT * from products where id ="' . $id . '"  ';

$result_fr1 = $link->query($sql_fr1);



if ($result_fr1 && ($result_fr1->num_rows > 0)) {

  while ($row_fr1 = mysqli_fetch_assoc($result_fr1)) {


    $show_product = $row_fr1["prourl"];
  }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MauCNC</title>
  <link rel="shortcut icon" href="imgs/logo/logomt.jpg">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <style>
    @import url('https://openseadragon.github.io/openseadragon/openseadragon.min.css');
  </style>
  <style>
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    header {
      background-color: #f8f9fa;
      padding: 10px;
      text-align: left;
    }

    header img {
      max-width: 100%;
      height: auto;
    }

    main {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
    }


    img {
      max-width: 100%;
      height: auto;
    }

    .logo {
      position: absolute;
      top: 10px;
      /* Adjust the top position as needed */
      left: 10px;
      /* Adjust the left position as needed */
      width: 50px;
      /* Adjust the width as needed */
      height: auto;
      /* Maintain aspect ratio */
    }

    footer {
      background-color: #343a40;
      color: white;
      text-align: center;
      padding: 1rem 0;
    }
  </style>
</head>

<body>
  <header class="container">
    <a href="./">
      <img class="img-fluid logo" src="./imgs/logo/logomt.jpg" alt="Logo">
    </a>
  </header>

  <main class="container">
    <div id="openseadragon1" style="width: 100%; height: 100vh;"></div>

    <!-- Include OpenSeadragon script -->
    <script src="https://openseadragon.github.io/openseadragon/openseadragon.min.js"></script>
    <script>
      const viewer = OpenSeadragon({
        id: 'openseadragon1',
        prefixUrl: 'https://openseadragon.github.io/openseadragon/images/',
        tileSources: {
          type: 'image',
          url: 'imgs/<?php echo $show_product ?>'
        }
      });
    </script>
  </main>

  <footer class="container">
    <div class="row">
      <div class="col-lg-6 col-md-12 mb-4 mb-md-0">
        <h5 class="text-uppercase">Mẫu CNC</h5>
        <p>Quản Lý: Thiện Bùi </p>
        <p>Phone: 0338790560 </p>
        <!-- Add other contact information as needed -->
      </div>

      <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
        <p id="tktyc">Thiết kế theo yêu cầu </p>
      </div>
    </div>

    <div class="text-center" style="background-color: rgba(0, 0, 0, 0.2);">
      <a class="text-dark" href="">MauTranhCNC</a>
    </div>
  </footer>

  <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>