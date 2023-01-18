<?php
/* Database credentials. Assuming you are running MySQL
server with default setting (user 'root' with no password) */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'ngovy_maucnc');

/* Attempt to connect to MySQL database */
$link =  mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}


if (!$link->set_charset("utf8")) {
    printf($link->error);
    exit();
} else {
}

// function get_product($userid,$link){
//     $row_fr3 = array();
//     $sql_fr1="SELECT user2 as id,friend_stt,create_time FROM relationships 
//          WHERE user1 = '  $userid  'and friend_stt=1;"; 
         
//     $result_fr1 = $link->query($sql_fr1);
//     if ($result_fr1->num_rows > 0){
//         while($row_fr1 = mysqli_fetch_assoc($result_fr1)){
//             $rf=$row_fr1["id"];
//             $fr1="SELECT * FROM users
//                 WHERE id = '  $rf  ';"; 
//             $rs=$link->query($fr1);
//             $r_fr1 = mysqli_fetch_assoc($rs);
//             array_push($row_fr3,$r_fr1["id"]);
//             //dien vo day
//         }
//     }
// }
