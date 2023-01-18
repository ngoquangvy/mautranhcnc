<?php
require_once "../includes/connectdb.php";
session_start();
if (isset($_SESSION['id'])) { 


$id = $_GET['t'];
$show_product="";
$sql_fr1='SELECT * from products where protype ="' . $id . '" ORDER BY id DESC '; 
$result_fr1 = $link->query($sql_fr1);

if ($result_fr1&&($result_fr1->num_rows > 0)){
    while($row_fr1 = mysqli_fetch_assoc($result_fr1)){
        $show_product = $show_product .'<div class="col-sm-3">
        <div class="imgre">
          <img class="img-fluid" src="../home/imgs/'. $row_fr1["prourl"] .'" alt="">
          </div>
          <div  class="description">
            <h5>'. $row_fr1["proname"] .'<button type="button" class="bg-danger text-white btndel" value="' . $row_fr1["prourl"] . '">DELETE</button></h5>
            <h7>'. $row_fr1["protype"] .'</h7>
            <p>'. $row_fr1["description"] .'</p>
          </div>
    </div>';
    }
}
echo $show_product;
}else
{
  echo "";
}


?>