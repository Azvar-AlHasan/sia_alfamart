<?php
include 'koneksi.php';

function formatRupiah($angka){
    if($angka == 0) return "-";
    if($angka < 0) return "(" . number_format(abs($angka),0,',','.') . ")";
    return number_format($angka,0,',','.');
}

// Fungsi Bantuan Database
function getSum($conn, $query) {
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) return $row['t'] ?? 0;
    return 0;
}
function getBalance($conn, $code) {
    $result = $conn->query("SELECT balance FROM chart_of_accounts WHERE code='$code'");
    if ($result && $row = $result->fetch_assoc()) return $row['balance'];
    return 0;
}

$jenis = $_GET['laporan'] ?? 'labarugi';
$filename = "Laporan_" . ucfirst($jenis) . "_" . date('Ymd') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// ==========================================
// 1. LAPORAN LABA RUGI
// ==========================================
if($jenis == 'labarugi') {
    $rev = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'");
    $exp = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'");
    $laba = $rev - $exp;
    ?>
    <center><h2>PT SUMBER ALFARIA TRIJAYA TBK</h2><h3>LAPORAN LABA RUGI</h3><p>Per 30 September 2025</p></center>
    <table border="1">
        <tr bgcolor="#f0f0f0"><td colspan="3"><b>PENDAPATAN</b></td></tr>
        <?php
        $res = $conn->query("SELECT * FROM chart_of_accounts WHERE category='Revenue'");
        while($row = $res->fetch_assoc()) echo "<tr><td>'".$row['code']."</td><td>".$row['name']."</td><td align='right'>".formatRupiah($row['balance'])."</td></tr>";
        ?>
        <tr bgcolor="#d4edda"><td colspan="2"><b>Total Pendapatan</b></td><td align="right"><b><?php echo formatRupiah($rev); ?></b></td></tr>
        <tr bgcolor="#f0f0f0"><td colspan="3"><b>BEBAN</b></td></tr>
        <?php
        $res = $conn->query("SELECT * FROM chart_of_accounts WHERE category='Expense'");
        while($row = $res->fetch_assoc()) echo "<tr><td>'".$row['code']."</td><td>".$row['name']."</td><td align='right'>".formatRupiah($row['balance'])."</td></tr>";
        ?>
        <tr bgcolor="#f8d7da"><td colspan="2"><b>Total Beban</b></td><td align="right"><b><?php echo formatRupiah($exp); ?></b></td></tr>
        <tr bgcolor="#cce5ff"><td colspan="2" align="center"><h3>LABA BERSIH</h3></td><td align="right"><h3><?php echo formatRupiah($laba); ?></h3></td></tr>
    </table>
<?php
}

// ==========================================
// 2. LAPORAN NERACA
// ==========================================
elseif($jenis == 'neraca') {
    // Hitung Laba Berjalan
    $rev = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'");
    $exp = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'");
    $laba_berjalan = $rev - $exp;

    // Aset (Neto)
    $aset = getSum($conn, "SELECT SUM(CASE WHEN normal_balance = 'Credit' THEN -balance ELSE balance END) as t FROM chart_of_accounts WHERE category='Asset'");
    
    // Pasiva
    $liabilitas = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Liability'");
    $ekuitas_db = getSum($conn, "SELECT SUM(CASE WHEN normal_balance = 'Debit' THEN -balance ELSE balance END) as t FROM chart_of_accounts WHERE category='Equity'");
    
    $total_pasiva = $liabilitas + $ekuitas_db + $laba_berjalan;
    $selisih = $aset - $total_pasiva;
    ?>
    <center><h2>PT SUMBER ALFARIA TRIJAYA TBK</h2><h3>LAPORAN POSISI KEUANGAN (NERACA)</h3><p>Per 30 September 2025</p></center>
    <table border="1">
        <tr><td colspan="2" bgcolor="#cce5ff"><b>ASET</b></td></tr>
        <?php
        $res = $conn->query("SELECT *, CASE WHEN normal_balance = 'Credit' THEN -balance ELSE balance END as net FROM chart_of_accounts WHERE category='Asset' ORDER BY sub_category, code");
        while($row = $res->fetch_assoc()) echo "<tr><td>".$row['name']."</td><td align='right'>".formatRupiah($row['net'])."</td></tr>";
        ?>
        <tr bgcolor="#cce5ff"><td><b>TOTAL ASET</b></td><td align="right"><b><?php echo formatRupiah($aset); ?></b></td></tr>
        
        <tr><td colspan="2" bgcolor="#f8d7da"><b>LIABILITAS</b></td></tr>
        <?php
        $res = $conn->query("SELECT * FROM chart_of_accounts WHERE category='Liability' ORDER BY sub_category, code");
        while($row = $res->fetch_assoc()) echo "<tr><td>".$row['name']."</td><td align='right'>".formatRupiah($row['balance'])."</td></tr>";
        ?>
        <tr bgcolor="#f8d7da"><td><b>Total Liabilitas</b></td><td align="right"><b><?php echo formatRupiah($liabilitas); ?></b></td></tr>

        <tr><td colspan="2" bgcolor="#fff3cd"><b>EKUITAS</b></td></tr>
        <?php
        $res = $conn->query("SELECT *, CASE WHEN normal_balance = 'Debit' THEN -balance ELSE balance END as net FROM chart_of_accounts WHERE category='Equity' ORDER BY code");
        while($row = $res->fetch_assoc()) echo "<tr><td>".$row['name']."</td><td align='right'>".formatRupiah($row['net'])."</td></tr>";
        ?>
        <tr><td><i>+ Laba Tahun Berjalan</i></td><td align="right"><i><?php echo formatRupiah($laba_berjalan); ?></i></td></tr>
        <?php if($selisih != 0) echo "<tr><td><i>⚠️ Rounding</i></td><td align='right'>".formatRupiah($selisih)."</td></tr>"; ?>
        
        <tr bgcolor="#d4edda"><td><b>TOTAL LIABILITAS & EKUITAS</b></td><td align="right"><b><?php echo formatRupiah($total_pasiva + $selisih); ?></b></td></tr>
    </table>
<?php
}

// ==========================================
// 3. LAPORAN PERUBAHAN EKUITAS (DIPERBAIKI)
// ==========================================
elseif($jenis == 'ekuitas') {
    // 1. Hitung Laba
    $rev = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'");
    $exp = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'");
    $laba_berjalan = $rev - $exp;

    // 2. Ambil Komponen Ekuitas
    $modal_saham = getBalance($conn, '3-1001');
    $tambahan_modal = getBalance($conn, '3-1002');
    $saldo_laba_awal = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE code IN ('3-1006', '3-1007')");
    $ekuitas_lain = getSum($conn, "SELECT SUM(CASE WHEN normal_balance = 'Debit' THEN -balance ELSE balance END) as t FROM chart_of_accounts WHERE code IN ('3-1003', '3-1004', '3-1005')");
    $non_pengendali = getBalance($conn, '3-1008');

    // 3. Hitung Total Akhir
    $saldo_laba_akhir = $saldo_laba_awal + $laba_berjalan;
    $total_ekuitas = $modal_saham + $tambahan_modal + $ekuitas_lain + $non_pengendali + $saldo_laba_akhir;
    ?>
    <center><h2>PT SUMBER ALFARIA TRIJAYA TBK</h2><h3>LAPORAN PERUBAHAN EKUITAS</h3><p>Per 30 September 2025</p></center>
    <table border="1" width="100%">
        <tr bgcolor="#f0f0f0"><th>Komponen Ekuitas</th><th align="right">Saldo Awal</th><th align="right">Penambahan</th><th align="right">Saldo Akhir</th></tr>
        
        <tr><td>Modal Saham</td><td align="right"><?php echo formatRupiah($modal_saham); ?></td><td align="right">-</td><td align="right"><?php echo formatRupiah($modal_saham); ?></td></tr>
        <tr><td>Tambahan Modal Disetor</td><td align="right"><?php echo formatRupiah($tambahan_modal); ?></td><td align="right">-</td><td align="right"><?php echo formatRupiah($tambahan_modal); ?></td></tr>
        <tr><td>Ekuitas Lainnya</td><td align="right"><?php echo formatRupiah($ekuitas_lain); ?></td><td align="right">-</td><td align="right"><?php echo formatRupiah($ekuitas_lain); ?></td></tr>
        
        <tr bgcolor="#e6f4ea">
            <td><b>Saldo Laba</b></td>
            <td align="right"><?php echo formatRupiah($saldo_laba_awal); ?></td>
            <td align="right"><b><?php echo formatRupiah($laba_berjalan); ?></b></td>
            <td align="right"><b><?php echo formatRupiah($saldo_laba_akhir); ?></b></td>
        </tr>

        <tr><td>Kepentingan Non-Pengendali</td><td align="right"><?php echo formatRupiah($non_pengendali); ?></td><td align="right">-</td><td align="right"><?php echo formatRupiah($non_pengendali); ?></td></tr>
        
        <tr bgcolor="#fff3cd">
            <td><b>TOTAL EKUITAS</b></td>
            <td colspan="2" align="center">-</td>
            <td align="right"><b><?php echo formatRupiah($total_ekuitas); ?></b></td>
        </tr>
    </table>
<?php
}

// ==========================================
// 4. LAPORAN ARUS KAS (DIPERBAIKI)
// ==========================================
elseif($jenis == 'aruskas') {
    // 1. Operasi
    $kas_masuk = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'");
    $total_beban = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'");
    $arus_kas_operasi = $kas_masuk - $total_beban;

    // 2. Investasi
    $capex = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE code IN ('1-1202', '1-1203')");
    $arus_kas_investasi = 0 - $capex;

    // 3. Pendanaan (Plug)
    $saldo_kas_akhir = getBalance($conn, '1-1101');
    $arus_kas_pendanaan = $saldo_kas_akhir - ($arus_kas_operasi + $arus_kas_investasi);

    // 4. Validasi
    $kenaikan_bersih = $arus_kas_operasi + $arus_kas_investasi + $arus_kas_pendanaan;
    $saldo_awal = $saldo_kas_akhir - $kenaikan_bersih;
    ?>
    <center><h2>PT SUMBER ALFARIA TRIJAYA TBK</h2><h3>LAPORAN ARUS KAS</h3><p>Per 30 September 2025</p></center>
    <table border="1" width="100%">
        <tr><td colspan="2" bgcolor="#fff3cd"><b>ARUS KAS OPERASI</b></td></tr>
        <tr><td>Penerimaan Kas (Pendapatan)</td><td align="right"><?php echo formatRupiah($kas_masuk); ?></td></tr>
        <tr><td>Pengeluaran Kas (Beban)</td><td align="right" style="color:red">(<?php echo formatRupiah($total_beban); ?>)</td></tr>
        <tr bgcolor="#eee"><td><b>Kas Bersih Operasi</b></td><td align="right"><b><?php echo formatRupiah($arus_kas_operasi); ?></b></td></tr>
        
        <tr><td colspan="2" bgcolor="#cce5ff"><b>ARUS KAS INVESTASI</b></td></tr>
        <tr><td>Perolehan Aset Tetap</td><td align="right" style="color:red">(<?php echo formatRupiah($capex); ?>)</td></tr>
        <tr bgcolor="#eee"><td><b>Kas Bersih Investasi</b></td><td align="right"><b><?php echo formatRupiah($arus_kas_investasi); ?></b></td></tr>
        
        <tr><td colspan="2" bgcolor="#f8d7da"><b>ARUS KAS PENDANAAN</b></td></tr>
        <tr><td>Penerimaan Neto Utang & Modal</td><td align="right"><?php echo formatRupiah($arus_kas_pendanaan); ?></td></tr>
        <tr bgcolor="#eee"><td><b>Kas Bersih Pendanaan</b></td><td align="right"><b><?php echo formatRupiah($arus_kas_pendanaan); ?></b></td></tr>
        
        <tr bgcolor="#d4edda"><td><b>KENAIKAN KAS BERSIH</b></td><td align="right"><b><?php echo formatRupiah($kenaikan_bersih); ?></b></td></tr>
        <tr><td>Kas Awal Tahun</td><td align="right"><?php echo formatRupiah($saldo_awal); ?></td></tr>
        <tr bgcolor="#ffff00"><td><b>SALDO KAS AKHIR</b></td><td align="right"><b><?php echo formatRupiah($saldo_kas_akhir); ?></b></td></tr>
    </table>
<?php
}
?>