<?php
$servername = "sql100.infinityfree.com";
$username = "if0_40594372";
$password = "xlCPLDKkokws3B";
$dbname = "if0_40594372_db_alfa";

// Aktifkan pelaporan error driver database
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    // echo "Koneksi Berhasil!"; // Uncomment ini kalau mau tes koneksi saja
} catch (mysqli_sql_exception $e) {
    die("KONEKSI GAGAL: " . $e->getMessage());
}
?>