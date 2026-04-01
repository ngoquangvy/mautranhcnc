<?php
// Test script to verify uploadpd.php with a dummy image
$url = 'http://localhost:8080/admin/uploadpd.php';

// Path to a valid image file to test with
$test_image = '../home/imgs/logo_watermark.png'; // Use the logo since it's a valid PNG

if (!file_exists($test_image)) {
    die("Test image not found at $test_image\n");
}

// Prepare POST data
$data = [
    'nameimg' => 'Test Product',
    'typeimg' => 'Test Category',
    'desimg' => 'This is a test upload from script',
    'file1' => new CURLFile($test_image, 'image/png', 'test_image.png')
];

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=test_session'); // We need a session, but uploadpd checks for $_SESSION['id']

// Note: To bypass the session check for this test, I'll temporarily disable it in uploadpd.php or use a real session.
// Actually, I'll just check the PHP error log after trying.

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: $response\n";
