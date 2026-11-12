<?php
date_default_timezone_set('Asia/Jakarta');

$dbHost = 'localhost';
$dbName = 'db_voting_kakang_senduk';
$dbUser = 'root';
$dbPass = '';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi MySQL pada config/koneksi.php.');
}
