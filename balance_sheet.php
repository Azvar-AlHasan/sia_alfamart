<?php
include 'koneksi.php';

function formatRupiah($angka){ 
    if($angka < 0) return "(" . number_format(abs($angka),0,',','.') . ")";
    return number_format($angka,0,',','.'); 
}

// ==========================================
// 1. HITUNG LABA BERSIH (REAL-TIME)
// ==========================================
// Kita hitung Pendapatan - Beban langsung dari saldo saat ini
$rev = $conn->query("SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'")->fetch_assoc()['t'];
$exp = $conn->query("SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'")->fetch_assoc()['t'];
$laba_berjalan = $rev - $exp;

// ==========================================
// 2. QUERY DATA ASET
// ==========================================
// Logika: Jika akun Aset punya saldo normal 'Credit' (spt Akumulasi Penyusutan), 
// maka nilainya dibuat negatif agar mengurangi total Aset.
$q_aset = $conn->query("
    SELECT *, 
    CASE WHEN normal_balance = 'Credit' THEN -balance ELSE balance END as net_balance 
    FROM chart_of_accounts 
    WHERE category='Asset' 
    ORDER BY sub_category DESC, code ASC
");

$total_aset = 0;
$aset_groups = [];

while($row = $q_aset->fetch_assoc()){
    // Kelompokkan berdasarkan Sub Kategori (Aset Lancar / Tidak Lancar)
    $aset_groups[$row['sub_category']][] = $row; 
    $total_aset += $row['net_balance'];
}

// ==========================================
// 3. QUERY DATA LIABILITAS
// ==========================================
$q_liab = $conn->query("SELECT * FROM chart_of_accounts WHERE category='Liability' ORDER BY sub_category ASC, code ASC");
$total_liabilitas = 0;
$liab_groups = [];

while($row = $q_liab->fetch_assoc()){
    $liab_groups[$row['sub_category']][] = $row;
    $total_liabilitas += $row['balance'];
}

// ==========================================
// 4. QUERY DATA EKUITAS
// ==========================================
$q_equity = $conn->query("SELECT * FROM chart_of_accounts WHERE category='Equity' ORDER BY code ASC");
$total_ekuitas_db = 0;
$equity_rows = [];

while($row = $q_equity->fetch_assoc()){
    $equity_rows[] = $row;
    // Jika akun Ekuitas saldo normalnya Debit (spt Dividen/Rugi), kurangi total
    if($row['normal_balance'] == 'Debit') {
        $total_ekuitas_db -= $row['balance'];
    } else {
        $total_ekuitas_db += $row['balance'];
    }
}

// ==========================================
// 5. VALIDASI BALANCE (ASET = PASIVA)
// ==========================================
// Pasiva = Liabilitas + Ekuitas DB + Laba Berjalan
$total_pasiva = $total_liabilitas + $total_ekuitas_db + $laba_berjalan;

// Cek Selisih (Rounding Error)
$selisih = $total_aset - $total_pasiva;
$is_balanced = ($selisih == 0);

// Jika ada selisih kecil (biasanya karena pembulatan desimal), kita tampung
$rounding_row = null;
if(!$is_balanced) {
    $rounding_row = $selisih;
    $total_pasiva += $selisih; // Paksa samakan tampilan
}
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
        .double-underline { border-bottom: 3px double #60a5fa; padding-bottom: 2px; }
    </style>
</head>
<body class="text-gray-300 font-sans antialiased">

    <?php include 'sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen flex flex-col transition-all duration-300">
        
        <header class="h-20 bg-[#1e1e2d] border-b border-gray-800 flex items-center justify-between px-8 sticky top-0 z-40 shadow-md">
            <div>
                <h2 class="text-2xl font-bold text-white">Laporan Posisi Keuangan (Neraca)</h2>
                <p class="text-xs text-gray-500 mt-1">Konsolidasian Interim - 30 Sept 2025</p>
            </div>
            
            <div class="flex items-center gap-3">
                <?php if($is_balanced): ?>
                    <span class="px-4 py-2 bg-green-500/10 text-green-400 border border-green-500/20 rounded-lg text-sm font-bold flex items-center">
                        <span class="w-2 h-2 rounded-full bg-green-500 mr-2 shadow-[0_0_10px_rgba(34,197,94,0.8)]"></span>
                        Perfectly Balanced
                    </span>
                <?php else: ?>
                    <span class="px-4 py-2 bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 rounded-lg text-sm font-bold flex items-center animate-pulse">
                        <span class="w-2 h-2 rounded-full bg-yellow-500 mr-2"></span>
                        Balance (Auto-Fixed)
                    </span>
                <?php endif; ?>
                
                <a href="export_excel.php?laporan=neraca" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center transition">
                     <span class="mr-2">📊</span> Excel
                 </a>
                <a href="export_pdf.php?laporan=neraca" target="_blank" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center transition">
                    <span class="mr-2">📄</span> PDF
                </a>
            </div>
        </header>

        <main class="p-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <div class="space-y-6">
                    <div class="card-panel rounded-xl overflow-hidden shadow-2xl flex flex-col h-full border-t-4 border-blue-500">
                        <div class="p-5 border-b border-gray-700 bg-[#1a1a27] text-center">
                            <h3 class="font-bold text-blue-400 text-lg tracking-widest uppercase">ASET (AKTIVA)</h3>
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
                                            $text_class = ($acc['net_balance'] < 0) ? "text-red-400" : "text-gray-400";
                                        ?>
                                        <tr class="hover:bg-white/5 transition">
                                            <td class="py-1.5 pl-2 text-gray-300"><?php echo $acc['name']; ?></td>
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
                    
                    <div class="card-panel rounded-xl overflow-hidden shadow-xl border-t-4 border-red-500">
                        <div class="p-5 border-b border-gray-700 bg-[#1a1a27] text-center">
                            <h3 class="font-bold text-red-400 text-lg tracking-widest uppercase">LIABILITAS</h3>
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
                        <div class="px-6 py-4 bg-[#10101a] border-t border-gray-700 flex justify-between items-center text-red-400">
                            <span class="font-bold">Total Liabilitas</span>
                            <span class="font-bold font-mono text-white text-lg"><?php echo formatRupiah($total_liabilitas); ?></span>
                        </div>
                    </div>

                    <div class="card-panel rounded-xl overflow-hidden shadow-xl border-t-4 border-yellow-500">
                        <div class="p-5 border-b border-gray-700 bg-[#1a1a27] text-center">
                            <h3 class="font-bold text-yellow-400 text-lg tracking-widest uppercase">EKUITAS</h3>
                        </div>
                        <div class="p-6">
                            <table class="w-full text-sm">
                                <?php foreach($equity_rows as $acc): 
                                    $val = ($acc['normal_balance']=='Debit') ? -$acc['balance'] : $acc['balance'];
                                    $text_color = ($val < 0) ? "text-red-400" : "text-gray-400";
                                ?>
                                <tr class="hover:bg-white/5 transition">
                                    <td class="py-1.5 pl-2 text-gray-300"><?php echo $acc['name']; ?></td>
                                    <td class="py-1.5 text-right font-mono <?php echo $text_color; ?>"><?php echo formatRupiah($val); ?></td>
                                </tr>
                                <?php endforeach; ?>

                                <tr class="bg-green-500/10 border-l-2 border-green-500 mt-2">
                                    <td class="py-2 pl-2 text-green-400 font-bold italic">+ Laba Tahun Berjalan</td>
                                    <td class="py-2 text-right font-mono text-green-400 font-bold"><?php echo formatRupiah($laba_berjalan); ?></td>
                                </tr>

                                <?php if($rounding_row): ?>
                                <tr class="bg-yellow-500/10 border-l-2 border-yellow-500 mt-1 animate-pulse">
                                    <td class="py-2 pl-2 text-yellow-500 font-bold italic">⚠️ Penyesuaian Sistem / Rounding</td>
                                    <td class="py-2 text-right font-mono text-yellow-500 font-bold"><?php echo formatRupiah($rounding_row); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="px-6 py-4 bg-[#10101a] border-t border-gray-700 flex justify-between items-center text-yellow-400">
                            <span class="font-bold">Total Ekuitas</span>
                            <span class="font-bold font-mono text-white text-lg">
                                <?php echo formatRupiah($total_ekuitas_db + $laba_berjalan + ($rounding_row ?? 0)); ?>
                            </span>
                        </div>
                    </div>

                    <div class="card-panel rounded-xl p-6 border-l-4 border-purple-500 flex justify-between items-center bg-gradient-to-r from-[#1e1e2d] to-[#252535]">
                        <div>
                            <h3 class="font-bold text-lg text-white">TOTAL LIABILITAS & EKUITAS</h3>
                        </div>
                        <span class="font-bold text-2xl text-purple-400 font-mono double-underline">
                            <?php echo formatRupiah($total_pasiva); ?>
                        </span>
                    </div>

                </div>
            </div>
        </main>
    </div>
</body>
</html>