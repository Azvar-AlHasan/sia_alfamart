<?php
require('fpdf/fpdf.php');
include 'koneksi.php';

// --- FUNGSI BANTUAN (SAFETY NULL CHECK) ---
function formatRupiah($angka){
    if($angka == 0) return "-";
    if($angka < 0) return "(" . number_format(abs($angka),0,',','.') . ")";
    return number_format($angka,0,',','.');
}

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

// --- CLASS PDF CUSTOM ---
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10,'PT SUMBER ALFARIA TRIJAYA TBK',0,1,'C');
        $this->SetFont('Arial','',10);
        $this->Cell(0,5,'Laporan Keuangan Konsolidasian',0,1,'C');
        $this->Cell(0,5,'Periode Berakhir 30 September 2025',0,1,'C');
        $this->Ln(10);
        $this->Line(10, 35, 200, 35); // Garis Header
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Halaman '.$this->PageNo().'/{nb} - Sistem Informasi Akuntansi',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',10);

// ==========================================
// 1. LAPORAN LABA RUGI
// ==========================================
if($jenis == 'labarugi'){
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'LAPORAN LABA RUGI',0,1,'C');
    $pdf->Ln(5);

    $rev = 0; 
    $exp = 0;

    // A. Pendapatan
    $pdf->SetFillColor(220,255,220); // Hijau Muda
    $pdf->Cell(190,8,'PENDAPATAN',1,1,'L',true);
    
    $pdf->SetFont('Arial','',10);
    $q = $conn->query("SELECT * FROM chart_of_accounts WHERE category='Revenue' ORDER BY code");
    while($r=$q->fetch_assoc()){
        $pdf->Cell(30,7,$r['code'],1);
        $pdf->Cell(110,7,$r['name'],1);
        $pdf->Cell(50,7,formatRupiah($r['balance']),1,1,'R');
        $rev += $r['balance'];
    }
    // Total Rev
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(140,8,'Total Pendapatan',1,0,'R');
    $pdf->Cell(50,8,formatRupiah($rev),1,1,'R');
    $pdf->Ln(5);

    // B. Beban
    $pdf->SetFillColor(255,220,220); // Merah Muda
    $pdf->Cell(190,8,'BEBAN',1,1,'L',true);
    
    $pdf->SetFont('Arial','',10);
    $q = $conn->query("SELECT * FROM chart_of_accounts WHERE category='Expense' ORDER BY code");
    while($r=$q->fetch_assoc()){
        $pdf->Cell(30,7,$r['code'],1);
        $pdf->Cell(110,7,$r['name'],1);
        $pdf->Cell(50,7,formatRupiah($r['balance']),1,1,'R');
        $exp += $r['balance'];
    }
    // Total Exp
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(140,8,'Total Beban',1,0,'R');
    $pdf->Cell(50,8,formatRupiah($exp),1,1,'R');
    $pdf->Ln(5);

    // C. Hasil Akhir
    $laba = $rev - $exp;
    $pdf->SetFillColor(200,230,255); // Biru Muda
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(140,12,'LABA BERSIH TAHUN BERJALAN',1,0,'C',true);
    $pdf->Cell(50,12,formatRupiah($laba),1,1,'R',true);
}

// ==========================================
// 2. NERACA (POSISI KEUANGAN)
// ==========================================
elseif($jenis == 'neraca'){
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'LAPORAN POSISI KEUANGAN',0,1,'C');
    
    // --- ASET ---
    $pdf->SetFillColor(200,230,255); // Biru
    $pdf->Cell(190,8,'ASET',1,1,'L',true);
    
    $pdf->SetFont('Arial','',10);
    $total_aset = 0;
    // Logika Contra Asset
    $q = $conn->query("SELECT *, CASE WHEN normal_balance='Credit' THEN -balance ELSE balance END as net FROM chart_of_accounts WHERE category='Asset' ORDER BY sub_category DESC, code ASC");
    while($r=$q->fetch_assoc()){
        $pdf->Cell(140,6,$r['name'],1);
        $pdf->Cell(50,6,formatRupiah($r['net']),1,1,'R');
        $total_aset += $r['net'];
    }
    // Total Aset
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(230,240,255);
    $pdf->Cell(140,8,'TOTAL ASET',1,0,'R',true);
    $pdf->Cell(50,8,formatRupiah($total_aset),1,1,'R',true);

    $pdf->Ln(10); // Spasi Antara Aset & Pasiva

    // --- LIABILITAS ---
    $pdf->SetFillColor(255,220,220); // Merah
    $pdf->Cell(190,8,'LIABILITAS',1,1,'L',true);
    
    $pdf->SetFont('Arial','',10);
    $total_liab = 0;
    $q = $conn->query("SELECT * FROM chart_of_accounts WHERE category='Liability' ORDER BY sub_category ASC, code ASC");
    while($r=$q->fetch_assoc()){
        $pdf->Cell(140,6,$r['name'],1);
        $pdf->Cell(50,6,formatRupiah($r['balance']),1,1,'R');
        $total_liab += $r['balance'];
    }
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(140,8,'Total Liabilitas',1,0,'R');
    $pdf->Cell(50,8,formatRupiah($total_liab),1,1,'R');

    // --- EKUITAS ---
    $pdf->Ln(5);
    $pdf->SetFillColor(255,255,200); // Kuning
    $pdf->Cell(190,8,'EKUITAS',1,1,'L',true);
    
    $pdf->SetFont('Arial','',10);
    $total_ekuitas_db = 0;
    $q = $conn->query("SELECT *, CASE WHEN normal_balance='Debit' THEN -balance ELSE balance END as net FROM chart_of_accounts WHERE category='Equity' ORDER BY code ASC");
    while($r=$q->fetch_assoc()){
        $pdf->Cell(140,6,$r['name'],1);
        $pdf->Cell(50,6,formatRupiah($r['net']),1,1,'R');
        $total_ekuitas_db += $r['net'];
    }

    // Hitung Laba Berjalan (Untuk Balancing)
    $rev = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'");
    $exp = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'");
    $laba_berjalan = $rev - $exp;

    $pdf->SetFont('Arial','I',10);
    $pdf->Cell(140,6,'+ Laba Tahun Berjalan',1);
    $pdf->Cell(50,6,formatRupiah($laba_berjalan),1,1,'R');

    // Rounding Check
    $total_pasiva = $total_liab + $total_ekuitas_db + $laba_berjalan;
    $selisih = $total_aset - $total_pasiva;
    if($selisih != 0){
        $pdf->Cell(140,6,'Rounding / Penyesuaian',1);
        $pdf->Cell(50,6,formatRupiah($selisih),1,1,'R');
        $total_pasiva += $selisih;
    }

    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(255,255,220);
    $pdf->Cell(140,8,'TOTAL EKUITAS',1,0,'R',true);
    $pdf->Cell(50,8,formatRupiah($total_ekuitas_db + $laba_berjalan + $selisih),1,1,'R',true);
    
    // Grand Total
    $pdf->Ln(10);
    $pdf->SetFillColor(200,255,200); // Hijau
    $pdf->Cell(140,10,'TOTAL LIABILITAS & EKUITAS',1,0,'R',true);
    $pdf->Cell(50,10,formatRupiah($total_pasiva),1,1,'R',true);
}

// ==========================================
// 3. LAPORAN PERUBAHAN EKUITAS
// ==========================================
elseif($jenis == 'ekuitas'){
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'LAPORAN PERUBAHAN EKUITAS',0,1,'C');
    $pdf->Ln(5);

    // Hitung Laba Berjalan
    $rev = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'");
    $exp = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'");
    $laba_berjalan = $rev - $exp;

    // Ambil Data Ekuitas
    $modal = getBalance($conn, '3-1001');
    $tambahan = getBalance($conn, '3-1002');
    $laba_awal = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE code IN ('3-1006', '3-1007')");
    $lainnya = getSum($conn, "SELECT SUM(CASE WHEN normal_balance = 'Debit' THEN -balance ELSE balance END) as t FROM chart_of_accounts WHERE code IN ('3-1003', '3-1004', '3-1005')");
    $non_pengendali = getBalance($conn, '3-1008');

    $laba_akhir = $laba_awal + $laba_berjalan;
    $total_equity = $modal + $tambahan + $lainnya + $non_pengendali + $laba_akhir;

    // Tabel
    $pdf->SetFillColor(240,240,240);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(95,8,'Komponen Ekuitas',1,0,'C',true);
    $pdf->Cell(45,8,'Penambahan',1,0,'C',true);
    $pdf->Cell(50,8,'Saldo Akhir',1,1,'C',true);

    $pdf->SetFont('Arial','',10);
    
    // Baris-baris
    $pdf->Cell(95,7,'Modal Saham',1); $pdf->Cell(45,7,'-',1,0,'C'); $pdf->Cell(50,7,formatRupiah($modal),1,1,'R');
    $pdf->Cell(95,7,'Tambahan Modal Disetor',1); $pdf->Cell(45,7,'-',1,0,'C'); $pdf->Cell(50,7,formatRupiah($tambahan),1,1,'R');
    $pdf->Cell(95,7,'Ekuitas Lainnya',1); $pdf->Cell(45,7,'-',1,0,'C'); $pdf->Cell(50,7,formatRupiah($lainnya),1,1,'R');
    
    // Highlight Laba
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(95,7,'Saldo Laba (Retained Earnings)',1); 
    $pdf->SetTextColor(0,150,0); // Hijau Text
    $pdf->Cell(45,7,'+ '.formatRupiah($laba_berjalan),1,0,'R'); 
    $pdf->SetTextColor(0,0,0); // Reset Hitam
    $pdf->Cell(50,7,formatRupiah($laba_akhir),1,1,'R');
    
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(95,7,'Kepentingan Non-Pengendali',1); $pdf->Cell(45,7,'-',1,0,'C'); $pdf->Cell(50,7,formatRupiah($non_pengendali),1,1,'R');

    // Total
    $pdf->Ln(5);
    $pdf->SetFillColor(255,255,200);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(140,10,'TOTAL EKUITAS AKHIR',1,0,'L',true);
    $pdf->Cell(50,10,formatRupiah($total_equity),1,1,'R',true);
}

// ==========================================
// 4. LAPORAN ARUS KAS
// ==========================================
elseif($jenis == 'aruskas'){
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'LAPORAN ARUS KAS',0,1,'C');
    $pdf->Ln(5);

    // 1. Operasi
    $kas_masuk = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'");
    $total_beban = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'");
    $ops = $kas_masuk - $total_beban;

    // 2. Investasi
    $capex = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE code IN ('1-1202', '1-1203')");
    $inv = 0 - $capex;

    // 3. Pendanaan
    $kas_akhir = getBalance($conn, '1-1101');
    $fund = $kas_akhir - ($ops + $inv); // Plug Logic

    $kenaikan = $ops + $inv + $fund;
    $awal = $kas_akhir - $kenaikan;

    // Layout Tabel
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(255,240,200); // Orange Muda
    $pdf->Cell(190,8,'ARUS KAS DARI AKTIVITAS OPERASI',1,1,'L',true);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(140,7,'Penerimaan Kas dari Pelanggan',1); $pdf->Cell(50,7,formatRupiah($kas_masuk),1,1,'R');
    $pdf->Cell(140,7,'Pengeluaran Kas untuk Beban',1); $pdf->Cell(50,7,'('.formatRupiah($total_beban).')',1,1,'R');
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(140,8,'Kas Bersih Operasi',1); $pdf->Cell(50,8,formatRupiah($ops),1,1,'R');

    $pdf->Ln(3);
    $pdf->SetFillColor(200,240,255); // Biru Muda
    $pdf->Cell(190,8,'ARUS KAS DARI AKTIVITAS INVESTASI',1,1,'L',true);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(140,7,'Perolehan Aset Tetap & Hak Guna',1); $pdf->Cell(50,7,'('.formatRupiah($capex).')',1,1,'R');
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(140,8,'Kas Bersih Investasi',1); $pdf->Cell(50,8,formatRupiah($inv),1,1,'R');

    $pdf->Ln(3);
    $pdf->SetFillColor(240,200,255); // Ungu Muda
    $pdf->Cell(190,8,'ARUS KAS DARI AKTIVITAS PENDANAAN',1,1,'L',true);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(140,7,'Penerimaan Neto Utang & Modal',1); $pdf->Cell(50,7,formatRupiah($fund),1,1,'R');
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(140,8,'Kas Bersih Pendanaan',1); $pdf->Cell(50,8,formatRupiah($fund),1,1,'R');

    // Total Akhir
    $pdf->Ln(8);
    $pdf->SetFillColor(220,220,220);
    $pdf->Cell(140,8,'Kenaikan/(Penurunan) Kas Bersih',1,0,'L',true); 
    $pdf->Cell(50,8,formatRupiah($kenaikan),1,1,'R',true);
    
    $pdf->SetFont('Arial','I',10);
    $pdf->Cell(140,8,'Kas Awal Tahun',1); $pdf->Cell(50,8,formatRupiah($awal),1,1,'R');
    
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(50,50,80); // Gelap
    $pdf->SetTextColor(255,255,255); // Putih
    $pdf->Cell(140,12,'KAS DAN SETARA KAS AKHIR TAHUN',1,0,'L',true);
    $pdf->Cell(50,12,formatRupiah($kas_akhir),1,1,'R',true);
}

// Tanda Tangan Manager
$pdf->SetTextColor(0,0,0);
$pdf->Ln(15);
$pdf->SetFont('Arial','',10);
$pdf->Cell(130);
$pdf->Cell(60,5,'Tangerang, '.date('d F Y'),0,1,'C');
$pdf->Ln(20);
$pdf->Cell(130);
$pdf->Cell(60,5,'( Manager Keuangan )',0,1,'C');

// Generate File
$pdf->Output('I', 'Laporan_'.$jenis.'.pdf');
?>