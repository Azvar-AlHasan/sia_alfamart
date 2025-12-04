<?php
include 'koneksi.php';

// --- LOGIC PHP UNTUK BUKU BESAR ---
$kode_akun = isset($_GET['akun']) ? $_GET['akun'] : '';
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

$data_akun = null;
$saldo_awal = 0;
$transaksi = [];

if ($kode_akun) {
    // 1. Ambil Info Akun & Saldo Awal
    $sql_akun = "SELECT * FROM chart_of_accounts WHERE code = '$kode_akun'";
    $data_akun = $conn->query($sql_akun)->fetch_assoc();
    
    // Asumsi: Nilai di tabel chart_of_accounts adalah Saldo Awal per periode ini
    $saldo_awal = $data_akun['balance'];

    // 2. Ambil Transaksi Jurnal terkait Akun ini
    $sql_transaksi = "SELECT j.transaction_date, j.reference_no, j.description, i.debit, i.credit 
                      FROM journal_items i
                      JOIN journal_entries j ON i.journal_id = j.id
                      WHERE i.account_code = '$kode_akun' 
                      AND j.transaction_date BETWEEN '$tgl_mulai' AND '$tgl_akhir'
                      ORDER BY j.transaction_date ASC, j.id ASC";
    $result_transaksi = $conn->query($sql_transaksi);
}

function formatRupiah($angka){
    return "Rp " . number_format($angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Besar - Alfamart SIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #151521; }
        .card-panel { background-color: #1e1e2d; border: 1px solid #2b2b40; }
        select, input { color-scheme: dark; } /* Agar icon kalender putih */
    </style>
</head>
<body class="text-gray-300 font-sans antialiased">

    <?php include 'sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen flex flex-col transition-all duration-300">
        
        <header class="h-20 bg-[#1e1e2d] border-b border-gray-800 flex items-center px-8 sticky top-0 z-40">
            <h2 class="text-2xl font-bold text-white">Buku Besar (General Ledger)</h2>
        </header>

        <main class="p-8 space-y-6">
            
            <div class="card-panel rounded-xl p-6 shadow-lg">
                <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="w-full md:w-1/3">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Pilih Akun</label>
                        <select name="akun" class="w-full bg-[#151521] border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:border-blue-500 outline-none" onchange="this.form.submit()">
                            <option value="">-- Pilih Akun --</option>
                            <?php
                            $sql = "SELECT code, name FROM chart_of_accounts ORDER BY code ASC";
                            $res = $conn->query($sql);
                            while($row = $res->fetch_assoc()){
                                $selected = ($row['code'] == $kode_akun) ? 'selected' : '';
                                echo "<option value='".$row['code']."' $selected>".$row['code']." - ".$row['name']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="w-full md:w-1/4">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Dari Tanggal</label>
                        <input type="date" name="tgl_mulai" value="<?php echo $tgl_mulai; ?>" class="w-full bg-[#151521] border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:border-blue-500 outline-none">
                    </div>
                    <div class="w-full md:w-1/4">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Sampai Tanggal</label>
                        <input type="date" name="tgl_akhir" value="<?php echo $tgl_akhir; ?>" class="w-full bg-[#151521] border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:border-blue-500 outline-none">
                    </div>
                    <div class="w-full md:w-auto">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-bold shadow-lg shadow-blue-900/30 transition">
                            Tampilkan
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($data_akun): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="card-panel p-5 rounded-xl border-l-4 border-blue-500">
                    <p class="text-xs text-gray-500 uppercase font-bold">Nama Akun</p>
                    <p class="text-xl text-white font-bold mt-1"><?php echo $data_akun['name']; ?></p>
                    <p class="text-sm text-blue-400 font-mono mt-1"><?php echo $data_akun['code']; ?></p>
                </div>
                <div class="card-panel p-5 rounded-xl border-l-4 border-yellow-500">
                    <p class="text-xs text-gray-500 uppercase font-bold">Saldo Normal</p>
                    <p class="text-xl text-white font-bold mt-1 uppercase"><?php echo $data_akun['normal_balance']; ?></p>
                    <p class="text-sm text-yellow-400 mt-1">Kategori: <?php echo $data_akun['category']; ?></p>
                </div>
                <div class="card-panel p-5 rounded-xl border-l-4 border-green-500">
                    <p class="text-xs text-gray-500 uppercase font-bold">Saldo Awal (Per 30 Sept)</p>
                    <p class="text-xl text-white font-bold mt-1 font-mono tracking-tight">
                        <?php echo formatRupiah($saldo_awal); ?>
                    </p>
                </div>
            </div>

            <div class="card-panel rounded-xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-[#151521] text-gray-400 text-xs uppercase border-b border-gray-700">
                                <th class="px-6 py-4 font-bold">Tanggal</th>
                                <th class="px-6 py-4 font-bold">No. Ref</th>
                                <th class="px-6 py-4 font-bold w-1/3">Keterangan</th>
                                <th class="px-6 py-4 font-bold text-right text-blue-400">Debit</th>
                                <th class="px-6 py-4 font-bold text-right text-red-400">Kredit</th>
                                <th class="px-6 py-4 font-bold text-right text-white">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800 text-sm">
                            <tr class="bg-gray-800/30">
                                <td class="px-6 py-3 font-mono text-gray-500"><?php echo date('d/m/Y', strtotime($tgl_mulai)); ?></td>
                                <td class="px-6 py-3 font-mono text-gray-500">-</td>
                                <td class="px-6 py-3 font-bold text-yellow-500 italic">Saldo Awal</td>
                                <td class="px-6 py-3 text-right text-gray-500">-</td>
                                <td class="px-6 py-3 text-right text-gray-500">-</td>
                                <td class="px-6 py-3 text-right font-mono font-bold text-white bg-gray-800/50">
                                    <?php echo formatRupiah($saldo_awal); ?>
                                </td>
                            </tr>

                            <?php
                            $saldo_berjalan = $saldo_awal;
                            
                            if ($result_transaksi && $result_transaksi->num_rows > 0) {
                                while($row = $result_transaksi->fetch_assoc()) {
                                    // Logika Hitung Saldo Berjalan
                                    if ($data_akun['normal_balance'] == 'Debit') {
                                        $saldo_berjalan += $row['debit'] - $row['credit'];
                                    } else {
                                        $saldo_berjalan += $row['credit'] - $row['debit'];
                                    }
                                    
                                    // Tampilan
                                    echo "<tr class='hover:bg-white/5 transition duration-150'>";
                                    echo "<td class='px-6 py-4 font-mono text-gray-400'>" . date('d/m/Y', strtotime($row['transaction_date'])) . "</td>";
                                    echo "<td class='px-6 py-4 font-mono text-blue-400 text-xs'>" . $row['reference_no'] . "</td>";
                                    echo "<td class='px-6 py-4 text-gray-300'>" . $row['description'] . "</td>";
                                    echo "<td class='px-6 py-4 text-right font-mono text-gray-400'>" . ($row['debit'] > 0 ? formatRupiah($row['debit']) : '-') . "</td>";
                                    echo "<td class='px-6 py-4 text-right font-mono text-gray-400'>" . ($row['credit'] > 0 ? formatRupiah($row['credit']) : '-') . "</td>";
                                    echo "<td class='px-6 py-4 text-right font-mono font-bold text-white bg-gray-800/20'>" . formatRupiah($saldo_berjalan) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='px-6 py-8 text-center text-gray-500 italic'>Tidak ada transaksi tambahan pada periode ini.</td></tr>";
                            }
                            ?>
                        </tbody>
                        <tfoot class="bg-[#151521] border-t-2 border-gray-700">
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-right font-bold text-gray-400 uppercase tracking-wider">Saldo Akhir</td>
                                <td class="px-6 py-4 text-right font-mono font-bold text-green-400 text-lg">
                                    <?php echo formatRupiah($saldo_berjalan); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center h-96 card-panel rounded-xl text-center">
                    <div class="w-20 h-20 bg-gray-800 rounded-full flex items-center justify-center mb-4 animate-pulse">
                        <span class="text-4xl">👈</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Silakan Pilih Akun</h3>
                    <p class="text-gray-500 max-w-md">Pilih salah satu akun dari menu dropdown di atas untuk melihat rincian mutasi buku besar.</p>
                </div>
            <?php endif; ?>

        </main>
    </div>
</body>
</html>