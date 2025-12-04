<?php
include 'koneksi.php';
function formatRupiah($angka){ 
    if($angka < 0) return "(" . number_format(abs($angka),0,',','.') . ")";
    return number_format($angka,0,',','.'); 
}

// 1. HITUNG LABA BERSIH TAHUN BERJALAN (Otomatis dari Jurnal)
$rev = $conn->query("SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'")->fetch_assoc()['t'];
$exp = $conn->query("SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'")->fetch_assoc()['t'];
$laba_bersih = $rev - $exp;

// 2. AMBIL DATA EKUITAS DARI DB
// Kita anggap saldo di DB adalah saldo awal sebelum penutupan buku
$modal_saham = $conn->query("SELECT balance FROM chart_of_accounts WHERE code='3-1001'")->fetch_assoc()['balance'];
$tambahan_modal = $conn->query("SELECT balance FROM chart_of_accounts WHERE code='3-1002'")->fetch_assoc()['balance'];
$saldo_laba_awal = $conn->query("SELECT balance FROM chart_of_accounts WHERE code='3-1003'")->fetch_assoc()['balance'];
$dividen = $conn->query("SELECT balance FROM chart_of_accounts WHERE code='3-1004'")->fetch_assoc()['balance'];

// Hitung Saldo Akhir Laba Ditahan
$saldo_laba_akhir = $saldo_laba_awal + $laba_bersih - $dividen;

// Hitung Total Ekuitas Akhir
$total_ekuitas_akhir = $modal_saham + $tambahan_modal + $saldo_laba_akhir;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perubahan Ekuitas - Alfamart SIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #151521; }
        .card-panel { background-color: #1e1e2d; border: 1px solid #2b2b40; }
        th, td { white-space: nowrap; } /* Agar tabel tidak patah */
    </style>
</head>
<body class="text-gray-300 font-sans antialiased">

    <?php include 'sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen flex flex-col transition-all duration-300">
        <header class="h-20 bg-[#1e1e2d] border-b border-gray-800 flex items-center justify-between px-8 sticky top-0 z-40">
            <div>
                <h2 class="text-2xl font-bold text-white">Laporan Perubahan Ekuitas</h2>
                <p class="text-xs text-gray-500 mt-1">Periode Berakhir 30 September 2025</p>
            </div>
            <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm transition border border-gray-700">
                🖨️ Cetak
            </button>
        </header>

        <main class="p-8">
            
            <div class="card-panel rounded-xl overflow-hidden shadow-2xl">
                <div class="p-6 text-center border-b border-gray-700 bg-gradient-to-r from-[#1e1e2d] to-[#252535]">
                    <h3 class="text-xl font-bold text-white tracking-wide">PT SUMBER ALFARIA TRIJAYA Tbk</h3>
                    <p class="text-sm text-gray-400 uppercase mt-1">Laporan Perubahan Ekuitas Konsolidasian</p>
                    <p class="text-xs text-gray-500 mt-1">(Disajikan dalam Jutaan Rupiah)</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-right">
                        <thead>
                            <tr class="bg-[#151521] text-gray-400 text-xs uppercase font-bold border-b border-gray-700">
                                <th class="px-6 py-4 text-left w-1/3">Keterangan</th>
                                <th class="px-6 py-4 text-blue-400">Modal Saham</th>
                                <th class="px-6 py-4 text-blue-400">Tambahan Modal</th>
                                <th class="px-6 py-4 text-yellow-400">Saldo Laba</th>
                                <th class="px-6 py-4 text-white">Total Ekuitas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800 text-sm">
                            
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-6 py-4 text-left font-medium text-gray-300">Saldo Awal (1 Jan 2025)</td>
                                <td class="px-6 py-4 font-mono"><?php echo formatRupiah($modal_saham); ?></td>
                                <td class="px-6 py-4 font-mono"><?php echo formatRupiah($tambahan_modal); ?></td>
                                <td class="px-6 py-4 font-mono"><?php echo formatRupiah($saldo_laba_awal); ?></td>
                                <td class="px-6 py-4 font-mono font-bold text-gray-300">
                                    <?php echo formatRupiah($modal_saham + $tambahan_modal + $saldo_laba_awal); ?>
                                </td>
                            </tr>

                            <tr class="hover:bg-white/5 transition bg-green-900/10">
                                <td class="px-6 py-4 text-left font-medium text-green-400 flex items-center">
                                    <span class="mr-2">+</span> Laba Tahun Berjalan
                                </td>
                                <td class="px-6 py-4 font-mono text-gray-600">-</td>
                                <td class="px-6 py-4 font-mono text-gray-600">-</td>
                                <td class="px-6 py-4 font-mono text-green-400 font-bold"><?php echo formatRupiah($laba_bersih); ?></td>
                                <td class="px-6 py-4 font-mono text-green-400 font-bold"><?php echo formatRupiah($laba_bersih); ?></td>
                            </tr>

                            <tr class="hover:bg-white/5 transition">
                                <td class="px-6 py-4 text-left font-medium text-red-400 flex items-center">
                                    <span class="mr-2">-</span> Dividen Tunai
                                </td>
                                <td class="px-6 py-4 font-mono text-gray-600">-</td>
                                <td class="px-6 py-4 font-mono text-gray-600">-</td>
                                <td class="px-6 py-4 font-mono text-red-400">(<?php echo formatRupiah($dividen); ?>)</td>
                                <td class="px-6 py-4 font-mono text-red-400">(<?php echo formatRupiah($dividen); ?>)</td>
                            </tr>

                            <tr class="bg-gray-800 h-px"><td colspan="5"></td></tr>

                            <tr class="bg-[#1e1e2d] border-t-2 border-gray-600">
                                <td class="px-6 py-5 text-left font-bold text-white uppercase tracking-wider">Saldo Akhir (30 Sept 2025)</td>
                                <td class="px-6 py-5 font-mono font-bold text-blue-300 border-t border-blue-500/30">
                                    <?php echo formatRupiah($modal_saham); ?>
                                </td>
                                <td class="px-6 py-5 font-mono font-bold text-blue-300 border-t border-blue-500/30">
                                    <?php echo formatRupiah($tambahan_modal); ?>
                                </td>
                                <td class="px-6 py-5 font-mono font-bold text-yellow-400 text-lg border-t border-yellow-500/30">
                                    <?php echo formatRupiah($saldo_laba_akhir); ?>
                                </td>
                                <td class="px-6 py-5 font-mono font-bold text-white text-xl border-t border-white/30 bg-gray-700/20">
                                    <?php echo formatRupiah($total_ekuitas_akhir); ?>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-[#151521] text-xs text-gray-500 text-center border-t border-gray-800">
                    Sistem Informasi Akuntansi - Kelompok 5 (Alfamart Case Study)
                </div>
            </div>

        </main>
    </div>
</body>
</html>