<?php
include 'koneksi.php';
function formatRupiah($angka){ return "Rp " . number_format($angka,0,',','.'); }

// --- QUERY DATA REAL ---
$rev_data = $conn->query("SELECT * FROM chart_of_accounts WHERE category='Revenue'");
$exp_data = $conn->query("SELECT * FROM chart_of_accounts WHERE category='Expense'");

$total_rev = $conn->query("SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'")->fetch_assoc()['t'];
$total_exp = $conn->query("SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'")->fetch_assoc()['t'];
$laba_bersih = $total_rev - $total_exp;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laba Rugi - Alfamart SIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #151521; }
        .card-panel { background-color: #1e1e2d; border: 1px solid #2b2b40; }
    </style>
</head>
<body class="text-gray-300 font-sans antialiased">

    <?php include 'sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen flex flex-col">
        <header class="h-20 bg-[#1e1e2d] border-b border-gray-800 flex items-center justify-between px-8 sticky top-0 z-40">
            <h2 class="text-2xl font-bold text-white">Laporan Laba Rugi</h2>
            <div class="text-xs font-mono bg-gray-800 px-3 py-1 rounded text-gray-400">
                Periode: 1 Jan - 30 Sept 2025
            </div>
        </header>

        <main class="p-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                
                <div class="card-panel rounded-xl overflow-hidden flex flex-col h-full">
                    <div class="p-5 border-b border-gray-800 bg-gray-800/30 flex justify-between items-center">
                        <h3 class="font-bold text-green-400 tracking-wide uppercase">Pendapatan (Revenue)</h3>
                        <span class="text-xl">💰</span>
                    </div>
                    <div class="p-6 space-y-4 flex-1">
                        <?php while($row = $rev_data->fetch_assoc()): ?>
                        <div class="flex justify-between items-center group">
                            <span class="text-gray-400 group-hover:text-white transition"><?php echo $row['name']; ?></span>
                            <span class="font-mono text-white font-medium"><?php echo formatRupiah($row['balance']); ?></span>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="p-5 bg-green-500/10 border-t border-green-500/20 flex justify-between items-center">
                        <span class="font-bold text-green-500 text-sm uppercase">Total Pendapatan</span>
                        <span class="font-bold text-green-400 text-lg font-mono"><?php echo formatRupiah($total_rev); ?></span>
                    </div>
                </div>

                <div class="card-panel rounded-xl overflow-hidden flex flex-col h-full">
                    <div class="p-5 border-b border-gray-800 bg-gray-800/30 flex justify-between items-center">
                        <h3 class="font-bold text-red-400 tracking-wide uppercase">Beban (Expenses)</h3>
                        <span class="text-xl">💸</span>
                    </div>
                    <div class="p-6 space-y-4 flex-1">
                        <?php while($row = $exp_data->fetch_assoc()): ?>
                        <div class="flex justify-between items-center group border-b border-gray-800 pb-2 last:border-0">
                            <span class="text-gray-400 text-sm group-hover:text-white transition"><?php echo $row['name']; ?></span>
                            <span class="font-mono text-white text-sm"><?php echo formatRupiah($row['balance']); ?></span>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="p-5 bg-red-500/10 border-t border-red-500/20 flex justify-between items-center">
                        <span class="font-bold text-red-500 text-sm uppercase">Total Beban</span>
                        <span class="font-bold text-red-400 text-lg font-mono"><?php echo formatRupiah($total_exp); ?></span>
                    </div>
                </div>
            </div>

            <div class="card-panel rounded-xl p-8 flex items-center justify-between border-l-4 border-blue-500 bg-gradient-to-r from-[#1e1e2d] to-[#252535]">
                <div>
                    <h3 class="text-3xl font-bold text-white mb-2">Laba Bersih Tahun Berjalan</h3>
                    <p class="text-gray-500 text-sm">Net Income (Total Pendapatan - Total Beban)</p>
                </div>
                <div class="text-right">
                    <div class="text-4xl font-bold text-blue-400 font-mono tracking-tight mb-1">
                        <?php echo formatRupiah($laba_bersih); ?>
                    </div>
                    <div class="text-xs text-blue-500 font-medium uppercase tracking-wider bg-blue-500/10 px-3 py-1 rounded-full inline-block">
                        Profit Margin: <?php echo round(($laba_bersih / $total_rev) * 100, 2); ?>%
                    </div>
                </div>
            </div>

        </main>
    </div>
</body>
</html>