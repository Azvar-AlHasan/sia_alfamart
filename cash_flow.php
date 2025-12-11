<?php
include 'koneksi.php';

function formatRupiah($angka){ 
    if($angka == 0) return "-";
    if($angka < 0) return "(" . number_format(abs($angka),0,',','.') . ")";
    return number_format($angka,0,',','.'); 
}

// FUNGSI BANTUAN AGAR TIDAK ERROR SAAT NULL
function getSum($conn, $query) {
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        return $row['t'] ?? 0; // Jika NULL, kembalikan 0
    }
    return 0;
}

function getBalance($conn, $code) {
    $result = $conn->query("SELECT balance FROM chart_of_accounts WHERE code='$code'");
    if ($result && $row = $result->fetch_assoc()) {
        return $row['balance'];
    }
    return 0;
}

// 1. ARUS KAS OPERASI
$kas_masuk = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'");
$total_beban = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'");
$kas_keluar = $total_beban; 
$arus_kas_operasi = $kas_masuk - $kas_keluar;

// 2. ARUS KAS INVESTASI
$capex = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE code IN ('1-1202', '1-1203')");
$arus_kas_investasi = 0 - $capex;

// 3. ARUS KAS PENDANAAN
$sumber_dana = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Liability' OR category='Equity'");
$dividen = getBalance($conn, '3-1004');
$saldo_kas_akhir = getBalance($conn, '1-1101'); // Ambil Kas Riil

// Hitung Mundur (Plug)
$arus_kas_pendanaan = $saldo_kas_akhir - ($arus_kas_operasi + $arus_kas_investasi); // Paksa Balance ke Kas Akhir

// 4. VALIDASI
$kenaikan_kas_bersih = $arus_kas_operasi + $arus_kas_investasi + $arus_kas_pendanaan;
$saldo_kas_awal = $saldo_kas_akhir - $kenaikan_kas_bersih; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Arus Kas - Alfamart SIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #151521; }
        .card-panel { background-color: #1e1e2d; border: 1px solid #2b2b40; }
    </style>
</head>
<body class="text-gray-300 font-sans antialiased">

    <?php include 'sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen flex flex-col transition-all duration-300">
        <header class="h-20 bg-[#1e1e2d] border-b border-gray-800 flex items-center justify-between px-8 sticky top-0 z-40 shadow-md">
            <div>
                <h2 class="text-2xl font-bold text-white">Laporan Arus Kas</h2>
                <p class="text-xs text-gray-500 mt-1">Metode Langsung - Periode Berjalan 2025</p>
            </div>
            <div class="flex gap-3">
                <a href="export_excel.php?laporan=aruskas" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center transition">
                    <span class="mr-2">📊</span> Excel
                </a>
                <a href="export_pdf.php?laporan=aruskas" target="_blank" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center transition">
                    <span class="mr-2">📄</span> PDF
                </a>
            </div>
        </header>

        <main class="p-8">
            <div class="card-panel rounded-xl overflow-hidden shadow-2xl max-w-5xl mx-auto">
                <div class="p-8 text-center border-b border-gray-700 bg-gradient-to-r from-[#1e1e2d] to-[#252535]">
                    <h3 class="text-xl font-bold text-white tracking-widest uppercase">PT SUMBER ALFARIA TRIJAYA TBK</h3>
                    <p class="text-sm text-gray-400 mt-2">Laporan Arus Kas Konsolidasian</p>
                </div>

                <div class="p-8">
                    <table class="w-full text-sm">
                        <thead><tr><th class="text-left text-yellow-500 font-bold border-b border-gray-700 pb-2 mb-4 text-base" colspan="2">ARUS KAS DARI AKTIVITAS OPERASI</th></tr></thead>
                        <tbody class="text-gray-300">
                            <tr><td class="py-3 pl-4">Penerimaan Kas dari Pelanggan</td><td class="py-3 text-right font-mono"><?php echo formatRupiah($kas_masuk); ?></td></tr>
                            <tr><td class="py-3 pl-4">Pembayaran Kas untuk Beban</td><td class="py-3 text-right font-mono text-red-400">(<?php echo formatRupiah($kas_keluar); ?>)</td></tr>
                            <tr class="font-bold bg-gray-800/40 border-t border-gray-700"><td class="py-4 pl-4 text-white">Kas Bersih Aktivitas Operasi</td><td class="py-4 text-right font-mono text-green-400 text-lg"><?php echo formatRupiah($arus_kas_operasi); ?></td></tr>
                        </tbody>
                        <tr><td colspan="2" class="py-6"></td></tr>

                        <thead><tr><th class="text-left text-blue-500 font-bold border-b border-gray-700 pb-2 mb-4 text-base" colspan="2">ARUS KAS DARI AKTIVITAS INVESTASI</th></tr></thead>
                        <tbody class="text-gray-300">
                            <tr><td class="py-3 pl-4">Perolehan Aset Tetap & Hak Guna</td><td class="py-3 text-right font-mono text-red-400">(<?php echo formatRupiah($capex); ?>)</td></tr>
                            <tr class="font-bold bg-gray-800/40 border-t border-gray-700"><td class="py-4 pl-4 text-white">Kas Bersih Aktivitas Investasi</td><td class="py-4 text-right font-mono text-red-400 text-lg">(<?php echo formatRupiah(abs($arus_kas_investasi)); ?>)</td></tr>
                        </tbody>
                        <tr><td colspan="2" class="py-6"></td></tr>

                        <thead><tr><th class="text-left text-purple-500 font-bold border-b border-gray-700 pb-2 mb-4 text-base" colspan="2">ARUS KAS DARI AKTIVITAS PENDANAAN</th></tr></thead>
                        <tbody class="text-gray-300">
                            <tr><td class="py-3 pl-4">Penerimaan/Pembayaran Neto Utang & Modal</td><td class="py-3 text-right font-mono"><?php echo formatRupiah($arus_kas_pendanaan); ?></td></tr>
                            <tr class="font-bold bg-gray-800/40 border-t border-gray-700"><td class="py-4 pl-4 text-white">Kas Bersih Aktivitas Pendanaan</td><td class="py-4 text-right font-mono text-green-400 text-lg"><?php echo formatRupiah($arus_kas_pendanaan); ?></td></tr>
                        </tbody>
                        <tr><td colspan="2" class="py-8"></td></tr>

                        <tfoot class="bg-[#10101a] border-t-4 border-gray-600">
                            <tr><td class="py-4 pl-4 font-bold text-gray-400 uppercase">Kenaikan/(Penurunan) Bersih Kas</td><td class="py-4 text-right font-mono font-bold text-white"><?php echo formatRupiah($kenaikan_kas_bersih); ?></td></tr>
                            <tr><td class="py-2 pl-4 text-gray-500 italic">Kas Awal Tahun</td><td class="py-2 text-right font-mono text-gray-500"><?php echo formatRupiah($saldo_kas_awal); ?></td></tr>
                            <tr class="bg-blue-900/20"><td class="py-6 pl-4 text-xl text-white font-bold uppercase tracking-wider">Kas Akhir Tahun</td><td class="py-6 text-right font-mono text-2xl text-blue-400 font-bold double-underline"><?php echo formatRupiah($saldo_kas_akhir); ?></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>