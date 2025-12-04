<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_alfamart";

// Aktifkan pelaporan error driver database
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    // echo "Koneksi Berhasil!"; // Uncomment ini kalau mau tes koneksi saja
} catch (mysqli_sql_exception $e) {
    die("KONEKSI GAGAL: " . $e->getMessage());
}
?>