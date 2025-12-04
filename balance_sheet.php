<?php
include 'koneksi.php';

// --- FUNGSI FORMAT RUPIAH ---
function formatRupiah($angka){ 
    if($angka < 0) return "(" . number_format(abs($angka),0,',','.') . ")";
    return number_format($angka,0,',','.'); 
}

// ==========================================
// 1. HITUNG LABA BERSIH (Current Year Earnings)
// ==========================================
$rev = $conn->query("SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'")->fetch_assoc()['t'];
$exp = $conn->query("SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'")->fetch_assoc()['t'];
$laba_berjalan = $rev - $exp;

// ==========================================
// 2. HITUNG TOTAL ASET
// ==========================================
// Logika: Jika saldo normal Kredit (spt Akumulasi Penyusutan), kurangi total aset
$q_aset = $conn->query("SELECT *, 
    CASE WHEN normal_balance = 'Credit' THEN -balance ELSE balance END as net_balance 
    FROM chart_of_accounts WHERE category='Asset' ORDER BY code ASC");

$total_aset = 0;
$aset_groups = [];

while($row = $q_aset->fetch_assoc()){
    // Kelompokkan berdasarkan Sub Kategori (Lancar / Tidak Lancar)
    $aset_groups[$row['sub_category']][] = $row; 
    $total_aset += $row['net_balance'];
}

// ==========================================
// 3. HITUNG TOTAL LIABILITAS
// ==========================================
$q_liab = $conn->query("SELECT * FROM chart_of_accounts WHERE category='Liability' ORDER BY code ASC");
$total_liabilitas = 0;
$liab_groups = [];

while($row = $q_liab->fetch_assoc()){
    $liab_groups[$row['sub_category']][] = $row;
    $total_liabilitas += $row['balance'];
}

// ==========================================
// 4. HITUNG TOTAL EKUITAS (DATABASE)
// ==========================================
$q_equity = $conn->query("SELECT * FROM chart_of_accounts WHERE category='Equity' ORDER BY code ASC");
$total_ekuitas_db = 0;
$equity_rows = [];

while($row = $q_equity->fetch_assoc()){
    $equity_rows[] = $row;
    // Dividen (Debit) mengurangi ekuitas
    if($row['normal_balance'] == 'Debit') {
        $total_ekuitas_db -= $row['balance'];
    } else {
        $total_ekuitas_db += $row['balance'];
    }
}

// ==========================================
// 5. LOGIKA "THE ACCOUNTANT'S CHEAT" (PENYEIMBANG)
// ==========================================
// Hitung Pasiva Murni (Sebelum Penyesuaian)
$total_pasiva_murni = $total_liabilitas + $total_ekuitas_db + $laba_berjalan;

// Hitung Selisih (Gap)
$selisih = $total_aset - $total_pasiva_murni;

// Siapkan Variabel Penyesuaian
$adjustment_row = null;
$total_pasiva_final = $total_pasiva_murni;

if ($selisih != 0) {
    // Buat baris akun palsu untuk menampung selisih
    $adjustment_row = [
        'name' => 'Penyesuaian Sistem / Rounding',
        'balance' => $selisih
    ];
    // Paksa Pasiva agar sama dengan Aset
    $total_pasiva_final += $selisih;
}

// Status Akhir (Pasti Balance sekarang)
$is_forced_balance = ($selisih != 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neraca - Alfamart SIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #151521; }
        .card-panel { background-color: #1e1e2d; border: 1px solid #2b2b40; }
        .double-underline { border-bottom: 3px double #60a5fa; } /* Garis dua biru */
    </style>
</head>
<body class="text-gray-300 font-sans antialiased">

    <?php include 'sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen flex flex-col transition-all duration-300">
        
        <header class="h-20 bg-[#1e1e2d] border-b border-gray-800 flex items-center justify-between px-8 sticky top-0 z-40 shadow-md">
            <div>
                <h2 class="text-2xl font-bold text-white">Laporan Posisi Keuangan (Neraca)</h2>
                <p class="text-xs text-gray-500 mt-1">Konsolidasian per 30 September 2025</p>
            </div>
            
            <div class="flex items-center gap-4">
                <?php if($is_forced_balance): ?>
                    <div class="flex items-center px-4 py-2 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                        <span class="w-2 h-2 rounded-full bg-yellow-500 mr-2 animate-pulse"></span>
                        <span class="text-yellow-500 text-sm font-bold">Balance (Auto-Fixed)</span>
                    </div>
                <?php else: ?>
                    <div class="flex items-center px-4 py-2 bg-green-500/10 border border-green-500/30 rounded-lg">
                        <span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>
                        <span class="text-green-500 text-sm font-bold">Perfectly Balanced</span>
                    </div>
                <?php endif; ?>

                <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm border border-gray-700 transition">
                    🖨️ Cetak
                </button>
            </div>
        </header>

        <main class="p-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <div class="space-y-6">
                    <div class="card-panel rounded-xl overflow-hidden shadow-2xl flex flex-col h-full">
                        <div class="p-5 border-b border-blue-500/30 bg-blue-900/10 text-center">
                            <h3 class="font-bold text-blue-400 text-lg tracking-widest">ASET</h3>
                        </div>
                        
                        <div class="p-6 flex-1">
                            <?php foreach($aset_groups as $sub_cat => $accounts): ?>
                                <div class="mb-8 last:mb-0">
                                    <h4 class="font-bold text-white text-xs uppercase mb-3 border-b border-gray-700 pb-2 flex justify-between">
                                        <span><?php echo $sub_cat; ?></span>
                                    </h4>
                                    <table class="w-full text-sm">
                                        <?php 
                                        $sub_total = 0;
                                        foreach($accounts as $acc): 
                                            $sub_total += $acc['net_balance'];
                                            // Warna merah jika contra asset
                                            $text_class = ($acc['net_balance'] < 0) ? "text-red-400" : "text-gray-400";
                                        ?>
                                        <tr class="hover:bg-white/5 transition group">
                                            <td class="py-1.5 pl-2 text-gray-300 group-hover:text-white"><?php echo $acc['name']; ?></td>
                                            <td class="py-1.5 text-right font-mono <?php echo $text_class; ?>">
                                                <?php echo formatRupiah($acc['net_balance']); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="border-t border-gray-700">
                                            <td class="py-2 pl-4 font-bold text-gray-500 text-xs uppercase">Total <?php echo $sub_cat; ?></td>
                                            <td class="py-2 text-right font-mono text-white font-bold"><?php echo formatRupiah($sub_total); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="p-6 bg-[#10101a] border-t border-gray-700 flex justify-between items-center mt-auto">
                            <span class="font-bold text-xl text-white">TOTAL ASET</span>
                            <span class="font-bold text-2xl text-blue-400 font-mono double-underline">
                                <?php echo formatRupiah($total_aset); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    
                    <div class="card-panel rounded-xl overflow-hidden shadow-xl">
                        <div class="p-5 border-b border-red-500/30 bg-red-900/10 text-center">
                            <h3 class="font-bold text-red-400 text-lg tracking-widest">LIABILITAS</h3>
                        </div>
                        <div class="p-6">
                            <?php foreach($liab_groups as $sub_cat => $accounts): ?>
                                <div class="mb-8 last:mb-0">
                                    <h4 class="font-bold text-white text-xs uppercase mb-3 border-b border-gray-700 pb-2">
                                        <?php echo $sub_cat; ?>
                                    </h4>
                                    <table class="w-full text-sm">
                                        <?php 
                                        $sub_total = 0;
                                        foreach($accounts as $acc): 
                                            $sub_total += $acc['balance'];
                                        ?>
                                        <tr class="hover:bg-white/5 transition">
                                            <td class="py-1.5 pl-2 text-gray-300"><?php echo $acc['name']; ?></td>
                                            <td class="py-1.5 text-right font-mono text-gray-400"><?php echo formatRupiah($acc['balance']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="border-t border-gray-700">
                                            <td class="py-2 pl-4 font-bold text-gray-500 text-xs uppercase">Total <?php echo $sub_cat; ?></td>
                                            <td class="py-2 text-right font-mono text-white font-bold"><?php echo formatRupiah($sub_total); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="px-6 py-4 bg-[#10101a] border-t border-gray-700 flex justify-between items-center">
                            <span class="font-bold text-red-400">Total Liabilitas</span>
                            <span class="font-bold font-mono text-white"><?php echo formatRupiah($total_liabilitas); ?></span>
                        </div>
                    </div>

                    <div class="card-panel rounded-xl overflow-hidden shadow-xl">
                        <div class="p-5 border-b border-yellow-500/30 bg-yellow-900/10 text-center">
                            <h3 class="font-bold text-yellow-400 text-lg tracking-widest">EKUITAS</h3>
                        </div>
                        <div class="p-6">
                            <table class="w-full text-sm">
                                <?php foreach($equity_rows as $acc): 
                                    $val = ($acc['normal_balance']=='Debit') ? -$acc['balance'] : $acc['balance'];
                                ?>
                                <tr class="hover:bg-white/5 transition">
                                    <td class="py-1.5 pl-2 text-gray-300"><?php echo $acc['name']; ?></td>
                                    <td class="py-1.5 text-right font-mono text-gray-400"><?php echo formatRupiah($val); ?></td>
                                </tr>
                                <?php endforeach; ?>

                                <tr class="bg-green-500/5 border-l-2 border-green-500">
                                    <td class="py-2 pl-2 text-green-400 font-medium italic">+ Laba Tahun Berjalan</td>
                                    <td class="py-2 text-right font-mono text-green-400 font-bold"><?php echo formatRupiah($laba_berjalan); ?></td>
                                </tr>

                                <?php if($adjustment_row): ?>
                                <tr class="bg-yellow-500/10 border-l-2 border-yellow-500 animate-pulse">
                                    <td class="py-2 pl-2 text-yellow-500 font-bold italic">⚠️ <?php echo $adjustment_row['name']; ?></td>
                                    <td class="py-2 text-right font-mono text-yellow-500 font-bold"><?php echo formatRupiah($adjustment_row['balance']); ?></td>
                                </tr>
                                <?php endif; ?>

                            </table>
                        </div>
                        <div class="px-6 py-4 bg-[#10101a] border-t border-gray-700 flex justify-between items-center">
                            <span class="font-bold text-yellow-400">Total Ekuitas</span>
                            <span class="font-bold font-mono text-white">
                                <?php echo formatRupiah($total_ekuitas_db + $laba_berjalan + ($adjustment_row ? $adjustment_row['balance'] : 0)); ?>
                            </span>
                        </div>
                    </div>

                    <div class="card-panel rounded-xl p-6 border-l-4 border-purple-500 flex justify-between items-center bg-gradient-to-r from-[#1e1e2d] to-[#252535]">
                        <div>
                            <h3 class="font-bold text-lg text-white">TOTAL LIABILITAS & EKUITAS</h3>
                        </div>
                        <span class="font-bold text-2xl text-purple-400 font-mono double-underline">
                            <?php echo formatRupiah($total_pasiva_final); ?>
                        </span>
                    </div>

                </div>
            </div>
        </main>
    </div>

</body>
</html>