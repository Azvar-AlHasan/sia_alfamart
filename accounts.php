<?php
include 'koneksi.php';

function formatRupiah($angka){ 
    if($angka==0) return "-"; 
    if($angka < 0) return "(" . number_format(abs($angka),0,',','.') . ")";
    return "Rp " . number_format($angka,0,',','.'); 
}

// --- LOGIKA PENCARIAN ---
$search_keyword = ""; 
$where_clause = "";   

if (isset($_GET['q'])) {
    $search_keyword = $conn->real_escape_string($_GET['q']);
    // Kita filter di HAVING clause nanti karena ini aggregate function
}

// --- QUERY UTAMA: GABUNGAN SALDO AWAL + JURNAL ---
// Ini adalah "Jantung" perbaikannya. Kita menggunakan LEFT JOIN ke tabel jurnal
$sql = "
SELECT 
    c.code, 
    c.name, 
    c.category, 
    c.sub_category,
    c.normal_balance,
    c.balance as saldo_awal_db,
    -- Hitung Total Mutasi dari Jurnal
    COALESCE(SUM(
        CASE 
            WHEN c.normal_balance = 'Debit' THEN j.debit - j.credit
            ELSE j.credit - j.debit
        END
    ), 0) as total_mutasi,
    -- Saldo Akhir = Saldo Awal DB + Mutasi
    (c.balance + COALESCE(SUM(
        CASE 
            WHEN c.normal_balance = 'Debit' THEN j.debit - j.credit
            ELSE j.credit - j.debit
        END
    ), 0)) as saldo_akhir
FROM chart_of_accounts c
LEFT JOIN journal_items j ON c.code = j.account_code
GROUP BY c.code
";

// Tambahkan Filter Pencarian (Jika ada)
if($search_keyword) {
    $sql = "SELECT * FROM ($sql) as tabel_bayangan 
            WHERE code LIKE '%$search_keyword%' 
            OR name LIKE '%$search_keyword%' 
            OR category LIKE '%$search_keyword%'";
}

$sql .= " ORDER BY code ASC"; // Urutkan kode

$result = $conn->query($sql);
$total_akun = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Akun - Alfamart SIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #151521; }
        .card-panel { background-color: #1e1e2d; border: 1px solid #2b2b40; }
        .search-input:focus { box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5); }
    </style>
</head>
<body class="text-gray-300 font-sans antialiased">

    <?php include 'sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen flex flex-col transition-all duration-300">
        
        <header class="h-20 bg-[#1e1e2d] border-b border-gray-800 flex items-center justify-between px-8 sticky top-0 z-40 shadow-md">
            <h2 class="text-2xl font-bold text-white">Master Data Akun (COA)</h2>
            <div class="flex gap-3">
                <a href="accounts.php" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm transition border border-gray-700 flex items-center">
                    🔄 Refresh Saldo
                </a>
            </div>
        </header>

        <main class="p-8">
            <div class="card-panel rounded-xl overflow-hidden shadow-2xl">
                
                <div class="p-5 border-b border-gray-700 bg-[#1e1e2d] flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-400">
                        Total Akun: <span class="text-white font-bold text-lg ml-1"><?php echo $total_akun; ?></span>
                    </div>
                    
                    <form method="GET" action="" class="relative w-full md:w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input type="text" name="q" 
                               value="<?php echo htmlspecialchars($search_keyword); ?>" 
                               placeholder="Cari kode atau nama akun..." 
                               class="search-input w-full bg-[#151521] border border-gray-700 text-sm rounded-lg pl-10 pr-4 py-2.5 text-white outline-none transition"
                               onchange="this.form.submit()">
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-gray-400 text-xs uppercase bg-[#151521] border-b border-gray-700">
                                <th class="px-6 py-4 font-semibold w-24">Kode</th>
                                <th class="px-6 py-4 font-semibold w-1/3">Nama Akun</th>
                                <th class="px-6 py-4 font-semibold">Kategori</th>
                                <th class="px-6 py-4 font-semibold text-center">Posisi</th>
                                <th class="px-6 py-4 font-semibold text-right text-gray-500 text-xs">Mutasi</th>
                                <th class="px-6 py-4 font-semibold text-right text-blue-400">Saldo Akhir (IDR)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800 text-sm">
                            <?php
                            $current_sub = "";

                            if ($total_akun > 0) {
                                while($row = $result->fetch_assoc()):
                                    // Grouping Header
                                    if (empty($search_keyword) && $row['sub_category'] != $current_sub) {
                                        echo "<tr class='bg-gray-800/40'><td colspan='6' class='px-6 py-2.5 font-bold text-yellow-500 text-xs uppercase tracking-widest border-l-4 border-yellow-500'>" . $row['sub_category'] . "</td></tr>";
                                        $current_sub = $row['sub_category'];
                                    }
                                    
                                    // Warna Saldo
                                    $saldo_class = ($row['normal_balance'] == 'Credit') ? 'text-yellow-400' : 'text-white';
                                    
                                    // Indikator jika ada mutasi
                                    $mutasi_display = "";
                                    if($row['total_mutasi'] != 0) {
                                        $color = $row['total_mutasi'] > 0 ? "text-green-500" : "text-red-500";
                                        $icon = $row['total_mutasi'] > 0 ? "▲" : "▼";
                                        $mutasi_display = "<span class='text-xs $color'>$icon ".formatRupiah($row['total_mutasi'])."</span>";
                                    }
                            ?>
                            <tr class="hover:bg-white/5 transition duration-150 group">
                                <td class="px-6 py-4 font-mono text-blue-400 group-hover:text-blue-300">
                                    <?php echo $row['code']; ?>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-300">
                                    <?php echo $row['name']; ?>
                                </td>
                                <td class="px-6 py-4 text-gray-500 text-xs uppercase">
                                    <span class="bg-gray-800 px-2 py-1 rounded border border-gray-700">
                                        <?php echo $row['category']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-1 rounded text-xs font-bold <?php echo ($row['normal_balance']=='Debit') ? 'text-green-500' : 'text-red-500'; ?>">
                                        <?php echo substr($row['normal_balance'], 0, 1) . " (" . $row['normal_balance'] . ")"; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-mono">
                                    <?php echo $mutasi_display; ?>
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-bold text-lg tracking-tight <?php echo $saldo_class; ?>">
                                    <?php echo formatRupiah($row['saldo_akhir']); ?>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            } else {
                                echo "<tr><td colspan='6' class='p-12 text-center text-gray-500 italic'>Tidak ada akun yang ditemukan.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>