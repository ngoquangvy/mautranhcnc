<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
require_once "../includes/connectdb.php";
// Get the 'id' parameter from the request
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'ngovy_maucnc');
define('DB_PASSWORD', 'ashgfjAHGSFGH12314-');
define('DB_NAME', 'ngovy_maucnc');
try {
    $db = new PDO('mysql:host=localhost;dbname=ngovy_maucnc', DB_USERNAME, DB_PASSWORD);
    // set the PDO error mode to exception
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    fetchItems();
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
function fetchItems()
{
    global $db;
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    $sql = "SELECT * FROM products WHERE protype = '$id' ORDER BY id DESC";

    $result = $db->query($sql);

    $items = [];
    while ($row = $result->fetch()) {
        $items[] = $row;
    }

    return $items;
}
$items = fetchItems();

$json = json_encode($items);

header('Content-Type: application/json');
echo $json;
//echo json_encode($json);