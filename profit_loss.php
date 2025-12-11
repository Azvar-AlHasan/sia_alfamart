<?php
include 'koneksi.php';
function formatRupiah($angka){ 
    if($angka < 0) return "(" . number_format(abs($angka),0,',','.') . ")";
    return number_format($angka,0,',','.'); 
}

// --- LOGIKA DINAMIS (REAL-TIME) ---
// 1. Ambil Total Pendapatan Langsung dari Saldo Akun Terkini
$q_rev = $conn->query("SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'");
$total_rev = $q_rev->fetch_assoc()['t'];

// 2. Ambil Total Beban Langsung dari Saldo Akun Terkini
$q_exp = $conn->query("SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'");
$total_exp = $q_exp->fetch_assoc()['t'];

// 3. Hitung Laba Bersih
$laba_bersih = $total_rev - $total_exp;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laba Rugi - Alfamart SIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #151521; }
        .card-panel { background-color: #1e1e2d; border: 1px solid #2b2b40; }
    </style>
</head>
<body class="text-gray-300 font-sans antialiased">

    <?php include 'sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen flex flex-col transition-all duration-300">
        <header class="h-20 bg-[#1e1e2d] border-b border-gray-800 flex items-center justify-between px-8 sticky top-0 z-40">
    <h2 class="text-2xl font-bold text-white">Laporan Laba Rugi</h2>
    <div class="flex gap-3">
        <a href="export_excel.php?laporan=labarugi" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center transition shadow-lg">
            <span class="mr-2">📊</span> Excel
        </a>
        <a href="export_pdf.php" target="_blank" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center transition shadow-lg">
            <span class="mr-2">📄</span> PDF
        </a>
    </div>
    </header>

        <main class="p-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                
                <div class="card-panel rounded-xl overflow-hidden flex flex-col h-full shadow-lg">
                    <div class="p-5 border-b border-gray-800 bg-gray-800/30 flex justify-between items-center">
                        <h3 class="font-bold text-green-400 tracking-wide uppercase">Pendapatan</h3>
                    </div>
                    <div class="p-6 space-y-4 flex-1">
                        <?php 
                        // Loop Akun Pendapatan
                        $sql = "SELECT * FROM chart_of_accounts WHERE category='Revenue' ORDER BY code";
                        $res = $conn->query($sql);
                        while($row = $res->fetch_assoc()):
                        ?>
                        <div class="flex justify-between items-center border-b border-gray-800 pb-2 last:border-0">
                            <div>
                                <span class="text-xs text-gray-500 mr-2"><?php echo $row['code']; ?></span>
                                <span class="text-gray-300"><?php echo $row['name']; ?></span>
                            </div>
                            <span class="font-mono text-white"><?php echo formatRupiah($row['balance']); ?></span>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="p-5 bg-green-500/10 border-t border-green-500/20 flex justify-between items-center">
                        <span class="font-bold text-green-500 uppercase">Total Pendapatan</span>
                        <span class="font-bold text-green-400 text-lg font-mono"><?php echo formatRupiah($total_rev); ?></span>
                    </div>
                </div>

                <div class="card-panel rounded-xl overflow-hidden flex flex-col h-full shadow-lg">
                    <div class="p-5 border-b border-gray-800 bg-gray-800/30 flex justify-between items-center">
                        <h3 class="font-bold text-red-400 tracking-wide uppercase">Beban</h3>
                    </div>
                    <div class="p-6 space-y-4 flex-1">
                        <?php 
                        // Loop Akun Beban
                        $sql = "SELECT * FROM chart_of_accounts WHERE category='Expense' ORDER BY code";
                        $res = $conn->query($sql);
                        while($row = $res->fetch_assoc()):
                        ?>
                        <div class="flex justify-between items-center border-b border-gray-800 pb-2 last:border-0">
                            <div>
                                <span class="text-xs text-gray-500 mr-2"><?php echo $row['code']; ?></span>
                                <span class="text-gray-300"><?php echo $row['name']; ?></span>
                            </div>
                            <span class="font-mono text-white"><?php echo formatRupiah($row['balance']); ?></span>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="p-5 bg-red-500/10 border-t border-red-500/20 flex justify-between items-center">
                        <span class="font-bold text-red-500 uppercase">Total Beban</span>
                        <span class="font-bold text-red-400 text-lg font-mono"><?php echo formatRupiah($total_exp); ?></span>
                    </div>
                </div>
            </div>

            <div class="card-panel rounded-xl p-8 flex items-center justify-between border-l-4 border-blue-500 bg-gradient-to-r from-[#1e1e2d] to-[#252535]">
                <div>
                    <h3 class="text-3xl font-bold text-white mb-2">Laba Bersih</h3>
                    <p class="text-gray-500 text-sm">Pendapatan - Beban</p>
                </div>
                <div class="text-right">
                    <div class="text-4xl font-bold text-blue-400 font-mono tracking-tight mb-1">
                        <?php echo formatRupiah($laba_bersih); ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>