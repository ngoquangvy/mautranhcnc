<?php

require_once "../includes/connectdb.php";



$id = $_GET['id'];

$show_product = "";

$sql_fr1 = 'SELECT * from products where protype ="' . $id . '" ORDER BY id DESC ';

$result_fr1 = $link->query($sql_fr1);



if ($result_fr1 && ($result_fr1->num_rows > 0)) {

    while ($row_fr1 = mysqli_fetch_assoc($result_fr1)) {

        $show_product = $show_product . '<div class="col-sm-3">
        <a href="../home/viewimg.php?id=' .  $row_fr1["id"] . '">

        <div class="imgre">

          <img class="img-fluid" src="imgs/' . $row_fr1["prourl"] . '" alt="">

          </div>

          <div  class="description">

            <h5>' . $row_fr1["proname"] . '</h5>

            <h7>' . $row_fr1["protype"] . '</h7>

            <p>' . $row_fr1["description"] . '</p>

          </div>
          </a>

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
            
                <a href="../home/protype.php?id=' .  $row_fr1["protype"] . '">
                  <p class="nav-link typepro type"   value="' . $row_fr1["protype"] . '" > ' . $row_fr1["protype"] . ' </p>
                  </a>
              ';
    }
}




?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link href="css/my.css" rel="stylesheet" type=”text/css”>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mẫu CNC</title>
    <link rel="shortcut icon" href="imgs/logo/logomt.jpg">
    <script src="js/jquery.js"></script>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <script src="js/bootstrap.min.js"></script>
    <style>
        @import url('https://maxcdn.bootstrapcdn.com/font-awesome/4.6.0/css/font-awesome.min.css');

        .ser-input {
            border-color: transparent;
            border-bottom-color: #fff;
            color: #fff;
        }

        .ser-input:focus {
            border-color: transparent;
            color: #fff;
            border-bottom: 1px solid #fff;
        }

        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        /* Track */
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        /* Handle */
        ::-webkit-scrollbar-thumb {
            background: #888;
        }

        /* Handle on hover */
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* .scrollul {height:60px; width:60%;overflow: scroll;}
    
    @media only screen and (max-width: 600px) {
        .scrollul {height:300px; width:100%;overflow: scroll;}
      } */


        .search-full-view {
            position: fixed;
            width: 100%;
            height: 100%;
            left: 0;
            top: 0;
            background: rgb(0, 0, 0);
            opacity: 0;
            z-index: -1;
            transition: .5s all;
            transform: scale(0);
        }

        .search-full-view.search-normal-screen {
            opacity: 1;
            z-index: 1;
            transform: scale(2);
        }

        .search-full-view .input-group {
            width: 80%;
            margin: 0 auto;
            top: 40%;
            height: 100px;
        }

        .search-full-view .input-group .form-control {
            background: transparent;
            border-bottom: 2px solid #fff;
            font-size: 6em;
            padding: 10px;
            vertical-align: unset;
            color: #cdcdcd;
        }

        .search-full-view .input-group .form-control:focus {
            border-color: #fff;
            border: 0 !important;
            border-bottom: 2px solid #fff !important;
        }

        .search-full-view .input-group .input-group-addon {
            background: transparent;
            font-size: 4em;
            color: #fff;
            border: 0;
            cursor: pointer;
        }

        .search-full-view .btn-close {
            background: transparent;
            border: 0;
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }

        .search-full-view .btn-close img {
            width: 60px;
        }

        .description {
            position: absolute;
            top: 202px;
            bottom: 2px;
        }

        .nav-link {
            padding: 0;
        }

        .type:active {

            background: rgb(236, 236, 216);

        }

        /* 
    .col-sm-3 img { visibility: hidden; }
    .col-sm-3 img.show { visibility: visible; } */


        .listcnc {
            padding-top: 140px;
        }

        .phone {
            position: fixed;
            bottom: 2px;
            right: 1px;
        }

        .headerr {
            z-index: 1;
            position: relative;
            position: fixed;
            /* Set the navbar to fixed position */
            top: 0;
            /* Position the navbar at the top of the page */
            width: 100%;
            /* Full width */
        }

        .sticky {
            position: fixed;
            top: 0;
            width: 100%;
        }

        /* Add some top padding to the page content to prevent sudden quick movement (as the navigation bar gets a new position at the top of the page (position:fixed and top:0) */
        .sticky+.content {
            padding-top: 60px;
        }


        .col-sm-3 {

            padding-top: 1px;
            position: relative;
            background-color: white;
            border: solid 1px rgb(227, 252, 0);
            height: 300px;
        }

        .img-fluid {
            max-height: 100%;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .img-fluid:hover {
            z-index: 9999;
            -ms-transform: scale(1.5);
            /* IE 9 */
            -webkit-transform: scale(1.5);
            /* Safari 3-8 */
            transform: scale(1.5);
        }

        .imgre {
            width: auto;
            height: 200px;
        }

        img.contacticon {
            width: 50px;
        }

        .footer-top {
            padding: 60px 0;
            background: #333;
            text-align: left;
            color: #aaa;
        }

        .footer-top h3 {
            padding-bottom: 10px;
            color: #fff;
        }

        .footer-about img.logo-footer {
            max-width: 200px;
            margin-top: 0;
            margin-bottom: 18px;
        }

        .footer-about p a {
            border: 0;
        }

        .footer-about p a:hover,
        .footer-about p a:focus {
            border: 0;
        }

        .footer-contact p {
            word-wrap: break-word;
        }

        .footer-contact i {
            padding-right: 10px;
            font-size: 18px;
            color: #666;
        }

        .footer-contact p a {
            border: 0;
        }

        .footer-contact p a:hover,
        .footer-contact p a:focus {
            border: 0;
        }

        .footer-links a {
            color: #aaa;
            border: 0;
        }

        .footer-links a:hover,
        .footer-links a:focus {
            color: #fff;
        }

        .footer-bottom {
            padding: 15px 0 17px 0;
            background: #444;
            text-align: left;
            color: #aaa;
        }

        .footer-social {
            padding-top: 3px;
            text-align: right;
        }

        .footer-social a {
            margin-left: 20px;
            color: #777;
            border: 0;
        }

        .footer-social a:hover,
        .footer-social a:focus {
            color: #79a05f;
            border: 0;
        }

        .footer-social i {
            font-size: 24px;
            vertical-align: middle;
        }

        .footer-copyright {
            padding-top: 5px;
        }

        .footer-copyright a {
            color: #fff;
            border: 0;
        }

        .footer-copyright a:hover,
        .footer-copyright a:focus {
            color: #aaa;
            border: 0;
        }


        .fontname1 {
            font-family: sans-serif;
        }

        div.typepro {
            padding-left: 10px;
            display: flex;
            background-color: #333;
            overflow: auto;
            white-space: nowrap;
        }

        div.typepro a {
            margin-left: 15px;
            display: inline-block;
            color: white;
            text-align: center;
            padding: 14px;
            text-decoration: none;

        }

        .dau {
            margin-top: 5px;
            z-index: 10;
            position: absolute;
            left: 1px;
        }

        .cuoi {
            position: absolute;
            right: 1px;
            z-index: 10;
            margin-top: 5px;

        }

        div.typepro a:hover {
            background-color: #777;
        }

        nav ul {
            height: 200px;
        }

        nav ul {
            overflow: hidden;
            overflow-y: scroll;
        }
    </style>

</head>

<body class="bg-light">
    <header class="headerr">
        <nav class="navbar navbar-expand-md navbar-dark bg-dark d-flex" id="navbar">
            <div class="container">
                <a class="navbar-brand" href="../home" class="text-white">Mẫu CNC</a>
                <button class="navbar-toggler " id="btnhide" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarsExampleDefault">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item">
                            <a class="nav-link contactt" id="contact">Contact</a>
                        </li>
                        <?php echo $show_protype ?>
                    </ul>
                </div>
                <div class="w-25">
                    <input type="text" class="form-control rounded-0 bg-dark ser-input" id="ser-input" placeholder="Search...">
                </div>
            </div>
        </nav>
        <!-- <nav class="navbar navbar-expand-md navbar-dark bg-dark d-flex" id="navbar"> -->
        <!-- <div class="container">
              <a class="navbar-brand text-white">Loại Mẫu:</a>
                <button class="navbar-toggler " type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button> -->
        <!-- <div class="typepro" id="typepro">
                <div class="dau">
                    <button onclick="left()">
                        << /button>
                </div>
                <div class="typeprochild" id="typeprochild" style="transform: translateX(5px);">
                  
    
    
    
                </div>
                <div class="cuoi">
                    <button onclick="right()">></button>
                </div>
            </div> -->
        <!-- </div> -->
        <!-- </nav> -->
    </header>


    <!-- end slide -->
    <div class="listcnc">
        <div class="container">
            <div class="row" id="products">
                <?php echo $show_product ?>
            </div>
        </div>

        <div class="phone" style="z-index:999999999" bg-white text-danger>
            <div><b>Contact</b></div>
            <a href="https://zalo.me/0338790560"><img class="contacticon" src="imgs/logo/zalo.png" alt=""> </a>
            <a href="https://www.facebook.com/thien.bui.12327608"><img class="contacticon" src="imgs/logo/fb.png" alt=""> </a>
        </div>
        <!-- Footer -->
        <footer class="bg-dark text-center text-lg-start text-white">
            <!-- Grid container -->
            <div class="container p-4">
                <!--Grid row-->
                <div class="row">
                    <!--Grid column-->
                    <div class="col-lg-6 col-md-12 mb-4 mb-md-0">
                        <h5 class="text-uppercase">Mẫu CNC</h5>
                        <p>Quản Lý: Thiện Bùi </p>
                        <p>Phone: 0338790560 </p>
                        <p>FaceBook: facebook.com/thien.bui.12327608 </p>
                        <p>Zalo:0338790560 </p>
                        <p>Gmail:buiminhthien96@gmail.com </p>
                    </div>
                    <!--Grid column-->

                    <!--Grid column-->
                    <div class="col-lg-3 col-md-6 mb-4 mb-md-0">

                        <p id="tktyc">Thiết kế theo yêu cầu </p>

                    </div>
                    <!--Grid column-->

                    <!--Grid column-->

                    <!--Grid column-->
                </div>
                <!--Grid row-->
            </div>
            <!-- Grid container -->

            <!-- Copyright -->
            <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2);">
                <a class="text-dark" href="">MauTranhCNC</a>
            </div>
            <!-- Copyright -->
        </footer>
        <!-- Footer -->

</body>

<script src="js/my.js"></script>


</html>