<?php
include 'koneksi.php';

// --- LOGIKA PENYIMPANAN JURNAL (BACKEND) ---
if(isset($_POST['simpan_jurnal'])){
    $ref        = $_POST['ref'];
    $tgl        = $_POST['tgl'];
    $desc       = $_POST['deskripsi_utama'];
    $tipe       = $_POST['tipe_jurnal'];
    
    // Ambil array data dari tabel input
    $akun_code  = $_POST['akun_code'];
    $ket_baris  = $_POST['ket_baris'];
    $debit      = $_POST['debit'];
    $kredit     = $_POST['kredit'];

    // Validasi Sederhana: Cek Balance Debit vs Kredit
    $total_debit = array_sum($debit);
    $total_kredit = array_sum($kredit);

    if($total_debit != $total_kredit){
        echo "<script>alert('GAGAL: Jurnal tidak balance! Total Debit: ".number_format($total_debit)." vs Kredit: ".number_format($total_kredit)."');</script>";
    } elseif ($total_debit == 0) {
        echo "<script>alert('GAGAL: Nominal tidak boleh nol.');</script>";
    } else {
        // Mulai Transaksi Database (Agar aman, semua tersimpan atau tidak sama sekali)
        $conn->begin_transaction();

        try {
            // 1. Simpan Header Jurnal
            $stmt = $conn->prepare("INSERT INTO journal_entries (transaction_date, reference_no, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $tgl, $ref, $desc);
            $stmt->execute();
            $journal_id = $conn->insert_id; // Ambil ID jurnal yang baru dibuat

            // 2. Simpan Detail Item & Update Saldo Akun
            $stmt_item = $conn->prepare("INSERT INTO journal_items (journal_id, account_code, debit, credit) VALUES (?, ?, ?, ?)");
            
            for($i = 0; $i < count($akun_code); $i++){
                if(!empty($akun_code[$i])){
                    $d = $debit[$i];
                    $k = $kredit[$i];
                    $kd = $akun_code[$i];

                    // Insert ke tabel journal_items
                    $stmt_item->bind_param("isdd", $journal_id, $kd, $d, $k);
                    $stmt_item->execute();

                    // 3. AUTO-UPDATE SALDO di chart_of_accounts
                    // Cek saldo normal akun dulu (Debit/Kredit)
                    $cek_akun = $conn->query("SELECT normal_balance FROM chart_of_accounts WHERE code = '$kd'")->fetch_assoc();
                    
                    if($cek_akun['normal_balance'] == 'Debit'){
                        // Jika Saldo Normal Debit: Tambah Debit, Kurang Kredit
                        $conn->query("UPDATE chart_of_accounts SET balance = balance + $d - $k WHERE code = '$kd'");
                    } else {
                        // Jika Saldo Normal Kredit: Tambah Kredit, Kurang Debit
                        $conn->query("UPDATE chart_of_accounts SET balance = balance + $k - $d WHERE code = '$kd'");
                    }
                }
            }

            $conn->commit(); // Simpan permanen
            echo "<script>alert('BERHASIL! Jurnal tersimpan dan Saldo Akun telah diperbarui.'); window.location='journals.php';</script>";

        } catch (Exception $e) {
            $conn->rollback(); // Batalkan jika ada error
            echo "<script>alert('ERROR SISTEM: " . $e->getMessage() . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Jurnal - Alfamart SIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #151521; }
        .card-panel { background-color: #1e1e2d; border: 1px solid #2b2b40; }
        
        /* Fix Dropdown Putih */
        select { background-color: #151521 !important; color: #d1d5db !important; }
        select option { background-color: #1e1e2d !important; color: #ffffff !important; padding: 10px; }
        input[type="date"] { color-scheme: dark; }
    </style>
</head>
<body class="text-gray-300 font-sans antialiased">

    <?php include 'sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen flex flex-col transition-all duration-300">
        <header class="h-20 bg-[#1e1e2d] border-b border-gray-800 flex items-center px-8 sticky top-0 z-40">
            <h2 class="text-2xl font-bold text-white">Input Jurnal Umum</h2>
        </header>

        <main class="p-8 space-y-8">
            
            <form method="POST" action="" class="card-panel rounded-xl p-6 shadow-xl">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">No. Transaksi</label>
                        <input type="text" name="ref" value="JU-<?php echo date('ymd'); ?>-<?php echo rand(100,999); ?>" class="w-full bg-[#151521] border border-gray-700 rounded-lg px-4 py-3 text-white font-mono focus:border-blue-500 outline-none" readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Tanggal</label>
                        <input type="date" name="tgl" value="<?php echo date('Y-m-d'); ?>" class="w-full bg-[#151521] border border-gray-700 rounded-lg px-4 py-3 text-white focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Tipe Jurnal</label>
                        <select name="tipe_jurnal" class="w-full bg-[#151521] border border-gray-700 rounded-lg px-4 py-3 text-white focus:border-blue-500 outline-none cursor-pointer">
                            <option value="Umum">Jurnal Umum (General)</option>
                            <option value="Penyesuaian">Jurnal Penyesuaian</option>
                        </select>
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Keterangan Utama</label>
                    <input type="text" name="deskripsi_utama" placeholder="Contoh: Pembayaran Listrik Toko..." class="w-full bg-[#151521] border border-gray-700 rounded-lg px-4 py-3 text-white focus:border-blue-500 outline-none" required>
                </div>

                <div class="border border-gray-700 rounded-xl overflow-hidden mb-6 bg-[#151521]/50">
                    <table class="w-full text-sm" id="jurnalTable">
                        <thead class="bg-[#1e1e2d] text-gray-400 border-b border-gray-700">
                            <tr>
                                <th class="p-4 text-left w-5/12 font-semibold">Nama Akun</th>
                                <th class="p-4 text-left font-semibold">Keterangan Baris</th>
                                <th class="p-4 text-right w-32 font-semibold">Debit</th>
                                <th class="p-4 text-right w-32 font-semibold">Kredit</th>
                                <th class="p-4 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800" id="tableBody">
                            <tr class="group hover:bg-gray-800/30 transition">
                                <td class="p-3">
                                    <select name="akun_code[]" class="w-full bg-[#151521] text-white outline-none p-2.5 border border-gray-700 rounded-lg focus:border-blue-500" required>
                                        <option value="" disabled selected>-- Pilih Akun --</option>
                                        <?php 
                                        $akun = $conn->query("SELECT code, name FROM chart_of_accounts ORDER BY code ASC");
                                        $options = "";
                                        while($a = $akun->fetch_assoc()) {
                                            $options .= "<option value='".$a['code']."'>".$a['code']." - ".$a['name']."</option>";
                                        }
                                        echo $options;
                                        ?>
                                    </select>
                                </td>
                                <td class="p-3"><input type="text" name="ket_baris[]" class="w-full bg-transparent text-gray-300 outline-none p-2 border-b border-transparent focus:border-blue-500" placeholder="Ket. Debit"></td>
                                <td class="p-3"><input type="number" name="debit[]" class="input-angka w-full bg-[#151521] text-white text-right p-2 rounded outline-none border border-gray-700 focus:border-blue-500 font-mono" placeholder="0" oninput="hitungTotal()"></td>
                                <td class="p-3"><input type="number" name="kredit[]" class="input-angka w-full bg-[#151521] text-white text-right p-2 rounded outline-none border border-gray-700 focus:border-blue-500 font-mono" placeholder="0" oninput="hitungTotal()"></td>
                                <td class="p-3 text-center text-gray-600 hover:text-red-500 cursor-pointer" onclick="hapusBaris(this)">x</td>
                            </tr>
                            
                            <tr class="group hover:bg-gray-800/30 transition">
                                <td class="p-3">
                                    <select name="akun_code[]" class="w-full bg-[#151521] text-white outline-none p-2.5 border border-gray-700 rounded-lg focus:border-blue-500" required>
                                        <option value="" disabled selected>-- Pilih Akun --</option>
                                        <?php echo $options; ?>
                                    </select>
                                </td>
                                <td class="p-3"><input type="text" name="ket_baris[]" class="w-full bg-transparent text-gray-300 outline-none p-2 border-b border-transparent focus:border-blue-500" placeholder="Ket. Kredit"></td>
                                <td class="p-3"><input type="number" name="debit[]" class="input-angka w-full bg-[#151521] text-white text-right p-2 rounded outline-none border border-gray-700 focus:border-blue-500 font-mono" placeholder="0" oninput="hitungTotal()"></td>
                                <td class="p-3"><input type="number" name="kredit[]" class="input-angka w-full bg-[#151521] text-white text-right p-2 rounded outline-none border border-gray-700 focus:border-blue-500 font-mono" placeholder="0" oninput="hitungTotal()"></td>
                                <td class="p-3 text-center text-gray-600 hover:text-red-500 cursor-pointer" onclick="hapusBaris(this)">x</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <button type="button" onclick="tambahBaris()" class="w-full p-3 bg-gray-800/50 hover:bg-gray-800 text-blue-400 text-sm font-medium transition flex items-center justify-center border-t border-gray-700">
                        <span class="mr-2 text-lg">+</span> Tambah Baris Baru
                    </button>
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-gray-700">
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">Total Balance</span>
                        <div class="flex gap-4 mt-1 font-mono">
                            <div class="text-sm">Debit: <span id="totalDebit" class="text-white font-bold">0</span></div>
                            <div class="text-sm">Kredit: <span id="totalKredit" class="text-white font-bold">0</span></div>
                            <div id="statusBalance" class="text-xs px-2 py-0.5 rounded bg-gray-700 text-gray-400">Belum Balance</div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="reset" class="px-6 py-2.5 bg-gray-800 hover:bg-gray-700 rounded-lg text-gray-300 text-sm font-medium transition border border-gray-700">Reset</button>
                        <button type="submit" name="simpan_jurnal" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 rounded-lg text-white text-sm font-bold shadow-lg shadow-blue-500/20 transition flex items-center">
                            Simpan Jurnal
                        </button>
                    </div>
                </div>
            </form>

            <div class="mt-10">
                <h3 class="text-white font-bold text-lg mb-4">5 Transaksi Terakhir</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-400">
                        <thead class="bg-[#1e1e2d] text-xs uppercase font-bold">
                            <tr>
                                <th class="p-3">Tanggal</th>
                                <th class="p-3">No. Ref</th>
                                <th class="p-3">Deskripsi</th>
                                <th class="p-3 text-right">Total Nilai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800 bg-[#1e1e2d]/50">
                            <?php
                            $log = $conn->query("SELECT j.*, SUM(i.debit) as total FROM journal_entries j JOIN journal_items i ON j.id = i.journal_id GROUP BY j.id ORDER BY j.id DESC LIMIT 5");
                            if($log->num_rows > 0){
                                while($l = $log->fetch_assoc()){
                                    echo "<tr class='hover:bg-gray-800/50'>";
                                    echo "<td class='p-3'>".date('d/m/Y', strtotime($l['transaction_date']))."</td>";
                                    echo "<td class='p-3 text-blue-400'>".$l['reference_no']."</td>";
                                    echo "<td class='p-3 text-white'>".$l['description']."</td>";
                                    echo "<td class='p-3 text-right font-mono'>Rp ".number_format($l['total'],0,',','.')."</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='p-4 text-center'>Belum ada data.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        // Simpan Opsi Akun ke variabel JS agar tidak perlu query ulang
        const optionsAkun = `<?php echo $options; ?>`;

        function tambahBaris() {
            const tableBody = document.getElementById('tableBody');
            const row = `
            <tr class="group hover:bg-gray-800/30 transition">
                <td class="p-3">
                    <select name="akun_code[]" class="w-full bg-[#151521] text-white outline-none p-2.5 border border-gray-700 rounded-lg focus:border-blue-500">
                        <option value="" disabled selected>-- Pilih Akun --</option>
                        ${optionsAkun}
                    </select>
                </td>
                <td class="p-3"><input type="text" name="ket_baris[]" class="w-full bg-transparent text-gray-300 outline-none p-2 border-b border-transparent focus:border-blue-500" placeholder="Keterangan"></td>
                <td class="p-3"><input type="number" name="debit[]" class="input-angka w-full bg-[#151521] text-white text-right p-2 rounded outline-none border border-gray-700 focus:border-blue-500 font-mono" placeholder="0" oninput="hitungTotal()"></td>
                <td class="p-3"><input type="number" name="kredit[]" class="input-angka w-full bg-[#151521] text-white text-right p-2 rounded outline-none border border-gray-700 focus:border-blue-500 font-mono" placeholder="0" oninput="hitungTotal()"></td>
                <td class="p-3 text-center text-gray-600 hover:text-red-500 cursor-pointer" onclick="hapusBaris(this)">x</td>
            </tr>`;
            tableBody.insertAdjacentHTML('beforeend', row);
        }

        function hapusBaris(btn) {
            btn.closest('tr').remove();
            hitungTotal();
        }

        function hitungTotal() {
            let debit = 0;
            let kredit = 0;
            
            document.querySelectorAll('input[name="debit[]"]').forEach(i => debit += Number(i.value));
            document.querySelectorAll('input[name="kredit[]"]').forEach(i => kredit += Number(i.value));

            document.getElementById('totalDebit').innerText = debit.toLocaleString('id-ID');
            document.getElementById('totalKredit').innerText = kredit.toLocaleString('id-ID');

            const status = document.getElementById('statusBalance');
            if(debit > 0 && debit === kredit) {
                status.innerText = "Balance ✅";
                status.className = "text-xs px-2 py-0.5 rounded bg-green-500/20 text-green-400 border border-green-500/30";
            } else {
                status.innerText = "Tidak Balance ⚠️";
                status.className = "text-xs px-2 py-0.5 rounded bg-red-500/20 text-red-400 border border-red-500/30";
            }
        }
    </script>
</body>
</html>