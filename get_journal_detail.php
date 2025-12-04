<?php
include 'koneksi.php';

header('Content-Type: application/json');

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Ambil Data Header
    $header = $conn->query("SELECT * FROM journal_entries WHERE id = '$id'")->fetch_assoc();
    
    // Ambil Data Detail Items
    $items = [];
    $sql_items = "SELECT i.*, c.name as account_name 
                  FROM journal_items i 
                  JOIN chart_of_accounts c ON i.account_code = c.code 
                  WHERE i.journal_id = '$id'";
    $res = $conn->query($sql_items);
    
    while($row = $res->fetch_assoc()){
        $items[] = $row;
    }
    
    echo json_encode(['header' => $header, 'items' => $items]);
}
?>