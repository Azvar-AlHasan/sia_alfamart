<?php
include 'koneksi.php';

// --- FUNGSI FORMAT RUPIAH ---
function formatRupiah($angka){
    if($angka == 0) return "-";
    // Format: Rp 1.000.000 (0 desimal)
    return "Rp " . number_format($angka, 0, ',', '.');
}

// ==========================================
// 1. LOGIKA PERHITUNGAN DATA (BACKEND)
// ==========================================

// A. PENDAPATAN (REVENUE)
$q_rev = $conn->query("SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'");
$rev = $q_rev->fetch_assoc()['t'];

// B. BEBAN (EXPENSE)
$q_exp = $conn->query("SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'");
$exp = $q_exp->fetch_assoc()['t'];

// C. LABA BERSIH (NET INCOME)
$laba = $rev - $exp;

// D. TOTAL ASET (ASSETS)
// Penting: Akun Contra (seperti Akumulasi Penyusutan) yang saldo normalnya 'Credit' harus MENGURANGI total aset
$q_aset = $conn->query("SELECT SUM(CASE WHEN normal_balance = 'Credit' THEN -balance ELSE balance END) as t 
                        FROM chart_of_accounts WHERE category='Asset'");
$aset = $q_aset->fetch_assoc()['t'];

// E. DATA UNTUK GRAFIK (CHART)
// HPP (Cost of Goods Sold)
$q_hpp = $conn->query("SELECT balance FROM chart_of_accounts WHERE code='5-1001'"); 
$hpp = $q_hpp->fetch_assoc()['balance'];

// Beban Operasional (Total Beban - HPP)
$beban_ops = $exp - $hpp;

// Aset Lancar vs Tidak Lancar
$q_aset_lancar = $conn->query("SELECT SUM(CASE WHEN normal_balance = 'Credit' THEN -balance ELSE balance END) as t FROM chart_of_accounts WHERE sub_category='Aset Lancar'");
$aset_lancar = $q_aset_lancar->fetch_assoc()['t'];
$aset_tidak_lancar = $aset - $aset_lancar;

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Alfamart SIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <h2 class="text-2xl font-bold text-white tracking-tight">Financial Dashboard</h2>
                <p class="text-xs text-gray-500 mt-1">Real-time Data Overview</p>
            </div>
            <div class="flex gap-4">
                <span class="px-4 py-2 rounded-full bg-blue-500/10 text-blue-400 text-xs font-bold border border-blue-500/20 flex items-center">
                    <span class="w-2 h-2 rounded-full bg-blue-500 mr-2 animate-pulse"></span>
                    Live Connection
                </span>
            </div>
        </header>

        <main class="p-8 space-y-8">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="card-panel p-6 rounded-2xl relative overflow-hidden group hover:border-green-500/50 transition">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Pendapatan Neto</p>
                    <h3 class="text-2xl font-bold text-white mt-2 truncate font-mono tracking-tight">
                        <?php echo formatRupiah($rev); ?>
                    </h3>
                    <p class="text-xs text-green-400 mt-2">Total Revenue</p>
                    <div class="absolute right-4 top-4 text-green-500/10 text-4xl group-hover:text-green-500/20 transition">💰</div>
                </div>

                <div class="card-panel p-6 rounded-2xl relative overflow-hidden group hover:border-blue-500/50 transition border-l-4 border-blue-500">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Laba Bersih</p>
                    <h3 class="text-2xl font-bold text-white mt-2 truncate font-mono tracking-tight">
                        <?php echo formatRupiah($laba); ?>
                    </h3>
                    <p class="text-xs text-blue-400 mt-2">Net Income (YTD)</p>
                    <div class="absolute right-4 top-4 text-blue-500/10 text-4xl group-hover:text-blue-500/20 transition">📈</div>
                </div>

                <div class="card-panel p-6 rounded-2xl relative overflow-hidden group hover:border-yellow-500/50 transition border-l-4 border-yellow-500">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Aset</p>
                    <h3 class="text-2xl font-bold text-white mt-2 truncate font-mono tracking-tight">
                        <?php echo formatRupiah($aset); ?>
                    </h3>
                    <p class="text-xs text-yellow-400 mt-2">Posisi Keuangan</p>
                    <div class="absolute right-4 top-4 text-yellow-500/10 text-4xl group-hover:text-yellow-500/20 transition">🏢</div>
                </div>

                <div class="card-panel p-6 rounded-2xl relative overflow-hidden group hover:border-red-500/50 transition border-l-4 border-red-500">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Beban</p>
                    <h3 class="text-2xl font-bold text-white mt-2 truncate font-mono tracking-tight">
                        <?php echo formatRupiah($exp); ?>
                    </h3>
                    <p class="text-xs text-red-400 mt-2">HPP & Operasional</p>
                    <div class="absolute right-4 top-4 text-red-500/10 text-4xl group-hover:text-red-500/20 transition">💸</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <div class="card-panel p-6 rounded-xl shadow-lg">
                    <h3 class="text-white font-bold mb-6 flex items-center">
                        <span class="w-2 h-6 bg-blue-500 mr-3 rounded-full"></span>
                        Komposisi Kinerja Keuangan
                    </h3>
                    <div class="relative h-72 w-full">
                        <canvas id="profitChart"></canvas>
                    </div>
                </div>

                <div class="card-panel p-6 rounded-xl shadow-lg">
                    <h3 class="text-white font-bold mb-6 flex items-center">
                        <span class="w-2 h-6 bg-yellow-500 mr-3 rounded-full"></span>
                        Struktur Aset
                    </h3>
                    <div class="relative h-72 w-full flex justify-center">
                        <canvas id="assetChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card-panel rounded-xl overflow-hidden shadow-2xl">
                <div class="px-6 py-5 border-b border-gray-700 bg-[#1e1e2d] flex justify-between items-center">
                    <h3 class="font-bold text-white text-lg">Aktivitas Jurnal Terkini</h3>
                    <a href="journals.php" class="text-sm text-blue-400 hover:text-blue-300 font-medium">Lihat Semua →</a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-gray-400 text-xs uppercase bg-[#151521] border-b border-gray-700">
                                <th class="px-6 py-4 font-semibold">Tanggal</th>
                                <th class="px-6 py-4 font-semibold">No. Ref</th>
                                <th class="px-6 py-4 font-semibold">Deskripsi</th>
                                <th class="px-6 py-4 font-semibold text-right">Nilai Transaksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800 text-sm">
                            <?php
                            // Query 5 Transaksi Terakhir (Header + Sum Detail)
                            $log_query = "SELECT j.id, j.transaction_date, j.reference_no, j.description, 
                                          (SELECT SUM(debit) FROM journal_items WHERE journal_id = j.id) as total_nilai
                                          FROM journal_entries j 
                                          ORDER BY j.id DESC 
                                          LIMIT 5";
                            $log = $conn->query($log_query);

                            if($log && $log->num_rows > 0){
                                while($l = $log->fetch_assoc()){
                                    echo "<tr class='hover:bg-white/5 transition'>";
                                    echo "<td class='px-6 py-4 font-mono text-gray-400'>" . date('d/m/Y', strtotime($l['transaction_date'])) . "</td>";
                                    echo "<td class='px-6 py-4 font-mono text-blue-400 text-xs'>" . $l['reference_no'] . "</td>";
                                    echo "<td class='px-6 py-4 text-white font-medium'>" . $l['description'] . "</td>";
                                    echo "<td class='px-6 py-4 text-right font-mono text-green-400'>" . formatRupiah($l['total_nilai']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='px-6 py-8 text-center text-gray-500 italic'>Belum ada transaksi yang direkam.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        // Data dari PHP dimasukkan ke variabel JS
        const dataHPP = <?php echo $hpp; ?>;
        const dataOps = <?php echo $beban_ops; ?>;
        const dataLaba = <?php echo $laba; ?>;
        const dataLancar = <?php echo $aset_lancar; ?>;
        const dataTidakLancar = <?php echo $aset_tidak_lancar; ?>;

        // 1. Bar Chart (Profitabilitas)
        new Chart(document.getElementById('profitChart'), {
            type: 'bar',
            data: {
                labels: ['Beban Pokok (HPP)', 'Beban Operasional', 'Laba Bersih'],
                datasets: [{
                    label: 'Nominal',
                    data: [dataHPP, dataOps, dataLaba],
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.8)', // Red
                        'rgba(249, 115, 22, 0.8)', // Orange
                        'rgba(34, 197, 94, 0.8)'   // Green
                    ],
                    borderRadius: 6,
                    barThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { 
                        grid: { color: '#2b2b40' },
                        ticks: { color: '#9ca3af', callback: function(value) { return 'Rp ' + (value/1000000000000).toFixed(1) + ' T'; } }
                    },
                    x: { grid: { display: false }, ticks: { color: '#d1d5db' } }
                }
            }
        });

        // 2. Doughnut Chart (Aset)
        new Chart(document.getElementById('assetChart'), {
            type: 'doughnut',
            data: {
                labels: ['Aset Lancar', 'Aset Tidak Lancar'],
                datasets: [{
                    data: [dataLancar, dataTidakLancar],
                    backgroundColor: ['#eab308', '#a855f7'], // Yellow, Purple
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { color: '#fff', padding: 20 } }
                },
                cutout: '70%'
            }
        });
    </script>

</body>
</html>