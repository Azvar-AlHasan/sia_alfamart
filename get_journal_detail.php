<?php
include 'koneksi.php';
header('Content-Type: application/json');

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // 1. Ambil Header Transaksi
    $header = $conn->query("SELECT * FROM journal_entries WHERE id = '$id'")->fetch_assoc();
    
    // 2. Ambil Detail Item Transaksi (Join dengan Nama Akun)
    $items = [];
    $sql_items = "SELECT i.*, c.name as account_name 
                  FROM journal_items i 
                  JOIN chart_of_accounts c ON i.account_code = c.code 
                  WHERE i.journal_id = '$id'";
    $res = $conn->query($sql_items);
    
    while($row = $res->fetch_assoc()){
        $items[] = $row;
    }
    
    // Kirim data JSON ke Javascript
    echo json_encode(['header' => $header, 'items' => $items]);
}
?>