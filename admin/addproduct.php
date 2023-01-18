<?php

require_once "../includes/connectdb.php";

session_start();

if (!isset($_SESSION['id'])) {header("location: ../admin"); }

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ADD PRODUCTS</title>

    <link rel="shortcut icon" href="imgs/logo/logomt.jpg">

    <script src="../home/js/jquery.js"></script>

    <link href="../home/css/bootstrap.min.css" rel="stylesheet">

    <script src="../home/js/bootstrap.min.js"></script>

      <script>

    //   function myFunction() {

    // var m = document.getElementById("message");

    // alert($("#files").files);

    // if(x.value != y.value ){

    //     m.innerHTML="image size must not exceed 300kb!";

    //     document.getElementById("btncp").disabled = true;  

    // }

    // else{

    //     m.innerHTML="";

    //     document.getElementById("btncp").disabled = false;

    // }

    // }

    $(document).ready(function(){



        var m = document.getElementById("message");

    $('#files').bind('change', function() {



        if(this.files[0].size < 921600){

            m.innerHTML="<p class='text-success'>Invalid</p>";

            document.getElementById("btncp").disabled = false;

        }else{

            m.innerHTML="<p class='text-danger'>Too Big, image size must not exceed 500kb!</p>";

            document.getElementById("btncp").disabled = true;

        }

    });

})

      </script>

</head>

<body>

    <header class="headerr">

        <nav class="navbar navbar-expand-md navbar-dark bg-dark d-flex" id="navbar">

            <div class="container">

                <a class="navbar-brand" href="#" onclick="location.href='../admin';" class="text-white">Mẫu CNC</a>

                <button class="navbar-toggler " type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">

                    <span class="navbar-toggler-icon"></span>

                </button>

                <div class="collapse navbar-collapse" id="navbarsExampleDefault">

                    <ul class="navbar-nav mr-auto">

                        <li class="nav-item active">

                            <a class="nav-link" href="#" onclick="location.href='../admin';">Home</a>

                        </li>

                        <li class="nav-item">

                            <a class="nav-link" href="#">ADD Products</a>

                        </li>

                    </ul>

                </div>

                <div class="w-25">

                    <input type="text" class="form-control rounded-0 bg-dark ser-input" id="ser-input" placeholder="Search...">

                </div>

            </div>

        </nav>

    </header>

    <div class="container">

        <form action="../admin/uploadpd.php" enctype="multipart/form-data" method="POST" class="border">

            <div class="border">

                 <p>Chọn hình ảnh:</p>

                 <input required="required"  id='files' type='file' name="images[]" multiple/>

                 <div class="text-red" id="message"></div>

            <div class="border">

                Tên mẫu: 

                <input name="nameimg" type="text" onchange="myFunction()"  required="required"  placeholder="Nhập tên ... ">

            </div>

            <div class="border">

                Loại

                <input name="typeimg" type="text" required="required"  placeholder="Nhập loại ... ">

            </div>

            <div class="border">

                <p>Ghi chú</p>

                <input name="desimg" type="text"  placeholder="Nhập ghi chú ... ">

            </div>

            <div>

                <button id="btncp" type="submit">SAVE</button>

            </div>

        </form>

    </div>

</body>

</html>