<?php
include 'koneksi.php';

// --- FUNGSI FORMAT RUPIAH ---
function formatRupiah($angka){
    if($angka == 0) return "-";
    return "Rp " . number_format($angka, 0, ',', '.');
}

// --- 1. QUERY KPI UTAMA ---
$rev = $conn->query("SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Revenue'")->fetch_assoc()['t'];
$exp = $conn->query("SELECT SUM(balance) as t FROM chart_of_accounts WHERE category='Expense'")->fetch_assoc()['t'];
$laba = $rev - $exp;

// --- 2. QUERY KHUSUS UNTUK CHART ---
// Data Aset Lancar vs Tidak Lancar (Perhatikan Logic Contra Asset)
$q_lancar = $conn->query("SELECT SUM(CASE WHEN normal_balance = 'Credit' THEN -balance ELSE balance END) as t 
                          FROM chart_of_accounts WHERE sub_category='Aset Lancar'");
$aset_lancar = $q_lancar->fetch_assoc()['t'];

$q_tidak_lancar = $conn->query("SELECT SUM(CASE WHEN normal_balance = 'Credit' THEN -balance ELSE balance END) as t 
                                FROM chart_of_accounts WHERE sub_category='Aset Tidak Lancar'");
$aset_tidak_lancar = $q_tidak_lancar->fetch_assoc()['t'];

$total_aset = $aset_lancar + $aset_tidak_lancar;

// Data Beban Breakdown (HPP vs Operasional)
$hpp = $conn->query("SELECT balance FROM chart_of_accounts WHERE code='5-1001'")->fetch_assoc()['balance'];
$beban_ops = $exp - $hpp; // Sisanya adalah beban operasional & pajak
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
                <p class="text-xs text-gray-500 mt-1">Laporan Konsolidasian Interim - 30 Sept 2025</p>
            </div>
            <div class="flex gap-4">
                <span class="px-4 py-2 rounded-full bg-green-500/10 text-green-500 text-xs font-bold border border-green-500/20 flex items-center">
                    <span class="w-2 h-2 rounded-full bg-green-500 mr-2 animate-pulse"></span> System Online
                </span>
            </div>
        </header>

        <main class="p-8 space-y-8">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="card-panel p-6 rounded-2xl relative overflow-hidden group">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Pendapatan</p>
                    <h3 class="text-xl font-bold text-white mt-2 truncate" title="<?php echo formatRupiah($rev); ?>">
                        <?php echo formatRupiah($rev); ?>
                    </h3>
                    <div class="absolute right-4 top-4 text-green-500/20 text-4xl">💰</div>
                </div>

                <div class="card-panel p-6 rounded-2xl border-l-4 border-blue-500 relative overflow-hidden">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Laba Bersih</p>
                    <h3 class="text-xl font-bold text-white mt-2 truncate" title="<?php echo formatRupiah($laba); ?>">
                        <?php echo formatRupiah($laba); ?>
                    </h3>
                    <div class="absolute right-4 top-4 text-blue-500/20 text-4xl">📈</div>
                </div>

                <div class="card-panel p-6 rounded-2xl border-l-4 border-yellow-500 relative overflow-hidden">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Aset</p>
                    <h3 class="text-xl font-bold text-white mt-2 truncate" title="<?php echo formatRupiah($total_aset); ?>">
                        <?php echo formatRupiah($total_aset); ?>
                    </h3>
                    <div class="absolute right-4 top-4 text-yellow-500/20 text-4xl">🏢</div>
                </div>

                <div class="card-panel p-6 rounded-2xl border-l-4 border-red-500 relative overflow-hidden">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Beban</p>
                    <h3 class="text-xl font-bold text-white mt-2 truncate" title="<?php echo formatRupiah($exp); ?>">
                        <?php echo formatRupiah($exp); ?>
                    </h3>
                    <div class="absolute right-4 top-4 text-red-500/20 text-4xl">💸</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <div class="card-panel p-6 rounded-xl shadow-lg">
                    <h3 class="text-white font-bold mb-4 flex items-center">
                        <span class="w-2 h-6 bg-blue-500 mr-3 rounded-full"></span>
                        Analisis Profitabilitas
                    </h3>
                    <div class="relative h-64 w-full">
                        <canvas id="profitChart"></canvas>
                    </div>
                </div>

                <div class="card-panel p-6 rounded-xl shadow-lg">
                    <h3 class="text-white font-bold mb-4 flex items-center">
                        <span class="w-2 h-6 bg-yellow-500 mr-3 rounded-full"></span>
                        Komposisi Aset
                    </h3>
                    <div class="relative h-64 w-full flex justify-center">
                        <canvas id="assetChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card-panel rounded-xl overflow-hidden shadow-2xl">
                <div class="px-6 py-5 border-b border-gray-700 bg-[#1e1e2d] flex justify-between items-center">
                    <h3 class="font-bold text-white text-lg">Neraca Saldo (Ringkasan)</h3>
                    <a href="ledger.php" class="text-sm text-blue-400 hover:text-blue-300">Lihat Buku Besar →</a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-gray-400 text-xs uppercase bg-[#151521] border-b border-gray-700">
                                <th class="px-6 py-4 font-semibold">Kode</th>
                                <th class="px-6 py-4 font-semibold">Nama Akun</th>
                                <th class="px-6 py-4 font-semibold text-right">Saldo Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800 text-sm">
                            <?php
                            // Tampilkan 5 Akun Terbesar saja agar tidak kepanjangan
                            $sql = "SELECT * FROM chart_of_accounts ORDER BY balance DESC LIMIT 5";
                            $result = $conn->query($sql);
                            while($row = $result->fetch_assoc()) {
                                echo "<tr class='hover:bg-white/5 transition'>";
                                echo "<td class='px-6 py-4 text-blue-400 font-mono text-xs'>" . $row["code"] . "</td>";
                                echo "<td class='px-6 py-4 font-medium text-gray-300'>" . $row["name"] . "</td>";
                                echo "<td class='px-6 py-4 text-right font-mono text-white'>" . formatRupiah($row['balance']) . "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        // --- 1. SETUP CHART PROFITABILITAS (Bar Chart) ---
        const ctxProfit = document.getElementById('profitChart').getContext('2d');
        new Chart(ctxProfit, {
            type: 'bar',
            data: {
                labels: ['Pendapatan', 'Beban HPP', 'Beban Operasional', 'Laba Bersih'],
                datasets: [{
                    label: 'Nominal (Triliun Rupiah)',
                    // Ambil Data PHP:
                    data: [
                        <?php echo $rev; ?>, 
                        <?php echo $hpp; ?>, 
                        <?php echo $beban_ops; ?>, 
                        <?php echo $laba; ?>
                    ],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.7)', // Green (Pendapatan)
                        'rgba(239, 68, 68, 0.7)', // Red (HPP)
                        'rgba(249, 115, 22, 0.7)', // Orange (Opex)
                        'rgba(59, 130, 246, 0.7)'  // Blue (Laba)
                    ],
                    borderColor: [
                        'rgba(34, 197, 94, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(249, 115, 22, 1)',
                        'rgba(59, 130, 246, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.raw;
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#2b2b40' }, // Garis grid gelap
                        ticks: { color: '#9ca3af' } // Text abu-abu
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#9ca3af' }
                    }
                }
            }
        });

        // --- 2. SETUP CHART ASET (Doughnut Chart) ---
        const ctxAsset = document.getElementById('assetChart').getContext('2d');
        new Chart(ctxAsset, {
            type: 'doughnut',
            data: {
                labels: ['Aset Lancar', 'Aset Tidak Lancar'],
                datasets: [{
                    // Ambil Data PHP:
                    data: [<?php echo $aset_lancar; ?>, <?php echo $aset_tidak_lancar; ?>],
                    backgroundColor: [
                        'rgba(234, 179, 8, 0.8)', // Yellow (Lancar)
                        'rgba(168, 85, 247, 0.8)' // Purple (Tidak Lancar)
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#d1d5db', padding: 20 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.raw;
                                let total = context.chart._metasets[context.datasetIndex].total;
                                let percentage = ((value / total) * 100).toFixed(1) + '%';
                                return percentage + ' (Rp ' + new Intl.NumberFormat('id-ID').format(value) + ')';
                            }
                        }
                    }
                }
            }
        });
    </script>

</body>
</html>