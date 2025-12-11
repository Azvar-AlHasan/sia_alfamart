<?php
include 'koneksi.php';

function formatRupiah($angka){ 
    if($angka < 0) return "(" . number_format(abs($angka),0,',','.') . ")";
    return number_format($angka,0,',','.'); 
}

// FUNGSI SAFETY QUERY
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

// 1. HITUNG LABA BERSIH TAHUN BERJALAN
$rev = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'");
$exp = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'");
$laba_tahun_berjalan = $rev - $exp;

// 2. AMBIL KOMPONEN EKUITAS
$modal_saham = getBalance($conn, '3-1001');
$tambahan_modal = getBalance($conn, '3-1002');
$saldo_laba_awal = getSum($conn, "SELECT SUM(balance) as t FROM chart_of_accounts WHERE code IN ('3-1006', '3-1007')");
$ekuitas_lain = getSum($conn, "SELECT SUM(CASE WHEN normal_balance = 'Debit' THEN -balance ELSE balance END) as t FROM chart_of_accounts WHERE code IN ('3-1003', '3-1004', '3-1005')");
$non_pengendali = getBalance($conn, '3-1008');

// 3. HITUNG TOTAL
$saldo_laba_akhir = $saldo_laba_awal + $laba_tahun_berjalan;
$total_ekuitas = $modal_saham + $tambahan_modal + $ekuitas_lain + $non_pengendali + $saldo_laba_akhir;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perubahan Ekuitas - Alfamart SIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style> body { background-color: #151521; } .card-panel { background-color: #1e1e2d; border: 1px solid #2b2b40; } </style>
</head>
<body class="text-gray-300 font-sans antialiased">

    <?php include 'sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen flex flex-col transition-all duration-300">
        <header class="h-20 bg-[#1e1e2d] border-b border-gray-800 flex items-center justify-between px-8 sticky top-0 z-40 shadow-md">
            <div>
                <h2 class="text-2xl font-bold text-white">Laporan Perubahan Ekuitas</h2>
                <p class="text-xs text-gray-500 mt-1">Konsolidasian Interim - 30 Sept 2025</p>
            </div>
            <div class="flex gap-3">
                <a href="export_excel.php?laporan=ekuitas" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center transition"><span class="mr-2">📊</span> Excel</a>
                <a href="export_pdf.php?laporan=ekuitas" target="_blank" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center transition"><span class="mr-2">📄</span> PDF</a>
            </div>
        </header>

        <main class="p-8 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="card-panel p-6 rounded-xl border-l-4 border-blue-500">
                    <p class="text-xs font-bold text-gray-500 uppercase">Modal Saham</p>
                    <h3 class="text-xl font-bold text-white mt-1"><?php echo formatRupiah($modal_saham); ?></h3>
                </div>
                <div class="card-panel p-6 rounded-xl border-l-4 border-green-500">
                    <p class="text-xs font-bold text-gray-500 uppercase">+ Laba Tahun Berjalan</p>
                    <h3 class="text-xl font-bold text-green-400 mt-1"><?php echo formatRupiah($laba_tahun_berjalan); ?></h3>
                </div>
                <div class="card-panel p-6 rounded-xl border-l-4 border-yellow-500 bg-gradient-to-r from-[#1e1e2d] to-[#2a2a35]">
                    <p class="text-xs font-bold text-gray-500 uppercase">Total Ekuitas Akhir</p>
                    <h3 class="text-xl font-bold text-yellow-400 mt-1"><?php echo formatRupiah($total_ekuitas); ?></h3>
                </div>
            </div>

            <div class="card-panel rounded-xl overflow-hidden shadow-2xl">
                <div class="p-6 border-b border-gray-700 bg-[#1e1e2d] flex justify-between items-center">
                    <h3 class="font-bold text-white text-lg">Rincian Pergerakan Ekuitas</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead><tr class="bg-[#151521] text-gray-400 text-xs uppercase font-bold border-b border-gray-700"><th class="px-6 py-4">Komponen</th><th class="px-6 py-4 text-right">Saldo Awal</th><th class="px-6 py-4 text-right">Penambahan</th><th class="px-6 py-4 text-right text-white">Saldo Akhir</th></tr></thead>
                        <tbody class="divide-y divide-gray-800 text-sm">
                            <tr><td class="px-6 py-4 text-gray-300">Modal Saham</td><td class="px-6 py-4 text-right font-mono text-gray-400"><?php echo formatRupiah($modal_saham); ?></td><td class="px-6 py-4 text-right">-</td><td class="px-6 py-4 text-right font-mono text-white"><?php echo formatRupiah($modal_saham); ?></td></tr>
                            <tr><td class="px-6 py-4 text-gray-300">Tambahan Modal</td><td class="px-6 py-4 text-right font-mono text-gray-400"><?php echo formatRupiah($tambahan_modal); ?></td><td class="px-6 py-4 text-right">-</td><td class="px-6 py-4 text-right font-mono text-white"><?php echo formatRupiah($tambahan_modal); ?></td></tr>
                            <tr><td class="px-6 py-4 text-gray-300">Ekuitas Lainnya</td><td class="px-6 py-4 text-right font-mono text-gray-400"><?php echo formatRupiah($ekuitas_lain); ?></td><td class="px-6 py-4 text-right">-</td><td class="px-6 py-4 text-right font-mono text-white"><?php echo formatRupiah($ekuitas_lain); ?></td></tr>
                            <tr class="bg-blue-900/10 border-l-4 border-blue-500"><td class="px-6 py-4 font-bold text-white">Saldo Laba</td><td class="px-6 py-4 text-right font-mono text-gray-400"><?php echo formatRupiah($saldo_laba_awal); ?></td><td class="px-6 py-4 text-right font-mono text-green-400 font-bold">+ <?php echo formatRupiah($laba_tahun_berjalan); ?></td><td class="px-6 py-4 text-right font-mono text-white font-bold text-lg"><?php echo formatRupiah($saldo_laba_akhir); ?></td></tr>
                            <tr><td class="px-6 py-4 text-gray-300">Kepentingan Non-Pengendali</td><td class="px-6 py-4 text-right font-mono text-gray-400"><?php echo formatRupiah($non_pengendali); ?></td><td class="px-6 py-4 text-right">-</td><td class="px-6 py-4 text-right font-mono text-white"><?php echo formatRupiah($non_pengendali); ?></td></tr>
                        </tbody>
                        <tfoot class="bg-[#10101a] border-t border-gray-700">
                            <tr><td colspan="3" class="px-6 py-5 text-right font-bold text-white uppercase text-lg">Total Ekuitas</td><td class="px-6 py-5 text-right font-bold text-yellow-400 font-mono text-xl underline decoration-double decoration-gray-600 underline-offset-4"><?php echo formatRupiah($total_ekuitas); ?></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>