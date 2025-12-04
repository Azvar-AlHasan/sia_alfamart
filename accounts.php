<?php
include 'koneksi.php';
function formatRupiah($angka){ if($angka==0) return "-"; return "Rp " . number_format($angka,0,',','.'); }
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
    </style>
</head>
<body class="text-gray-300 font-sans antialiased">

    <?php include 'sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen flex flex-col transition-all duration-300">
        
        <header class="h-20 bg-[#1e1e2d] border-b border-gray-800 flex items-center justify-between px-8 sticky top-0 z-40">
            <h2 class="text-2xl font-bold text-white">Master Data Akun</h2>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition shadow-lg shadow-blue-900/20 flex items-center gap-2">
                <span>+</span> Tambah Akun Baru
            </button>
        </header>

        <main class="p-8">
            <div class="card-panel rounded-xl overflow-hidden shadow-2xl">
                <div class="p-5 border-b border-gray-700 bg-[#1e1e2d] flex justify-between items-center">
                    <div class="text-sm text-gray-400">
                        Total Akun Terdaftar: <span class="text-white font-bold"><?php echo $conn->query("SELECT count(*) as c FROM chart_of_accounts")->fetch_assoc()['c']; ?></span>
                    </div>
                    <input type="text" placeholder="Cari kode atau nama akun..." class="bg-[#151521] border border-gray-700 text-sm rounded-lg px-4 py-2 w-64 text-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-gray-400 text-xs uppercase bg-[#151521] border-b border-gray-700">
                                <th class="px-6 py-4 font-semibold">Kode</th>
                                <th class="px-6 py-4 font-semibold">Nama Akun</th>
                                <th class="px-6 py-4 font-semibold">Kategori</th>
                                <th class="px-6 py-4 font-semibold text-center">Posisi Normal</th>
                                <th class="px-6 py-4 font-semibold text-right">Saldo Awal (IDR)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800 text-sm">
                            <?php
                            $sql = "SELECT * FROM chart_of_accounts ORDER BY code ASC";
                            $result = $conn->query($sql);
                            $current_sub = "";

                            while($row = $result->fetch_assoc()):
                                // Grouping Header
                                if ($row['sub_category'] != $current_sub) {
                                    echo "<tr class='bg-gray-800/40'><td colspan='5' class='px-6 py-2 font-bold text-yellow-500 text-xs uppercase tracking-widest border-l-4 border-yellow-500'>" . $row['sub_category'] . "</td></tr>";
                                    $current_sub = $row['sub_category'];
                                }
                            ?>
                            <tr class="hover:bg-white/5 transition duration-150 group">
                                <td class="px-6 py-4 font-mono text-blue-400 group-hover:text-blue-300"><?php echo $row['code']; ?></td>
                                <td class="px-6 py-4 font-medium text-gray-300"><?php echo $row['name']; ?></td>
                                <td class="px-6 py-4 text-gray-500 text-xs uppercase"><?php echo $row['category']; ?></td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-1 rounded text-xs font-bold <?php echo ($row['normal_balance']=='Debit') ? 'bg-green-500/10 text-green-500 border border-green-500/20' : 'bg-red-500/10 text-red-500 border border-red-500/20'; ?>">
                                        <?php echo $row['normal_balance']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-gray-300 tracking-tight"><?php echo formatRupiah($row['balance']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>