<?php
include 'koneksi.php';

// --- LOGIKA PENYIMPANAN JURNAL ---
if(isset($_POST['simpan_jurnal'])){
    $ref        = $_POST['ref'];
    $tgl        = $_POST['tgl'];
    $desc       = $_POST['deskripsi_utama'];
    $tipe       = $_POST['tipe_jurnal'];
    $akun_code  = isset($_POST['akun_code']) ? $_POST['akun_code'] : [];
    $ket_baris  = isset($_POST['ket_baris']) ? $_POST['ket_baris'] : [];
    $debit      = isset($_POST['debit']) ? $_POST['debit'] : [];
    $kredit     = isset($_POST['kredit']) ? $_POST['kredit'] : [];

    $total_debit = array_sum($debit);
    $total_kredit = array_sum($kredit);

    if (count($akun_code) < 2) {
        echo "<script>alert('Minimal harus ada 2 baris akun!');</script>";
    } elseif ($total_debit != $total_kredit) {
        echo "<script>alert('GAGAL: Jurnal Tidak Balance!');</script>";
    } elseif ($total_debit == 0) {
        echo "<script>alert('GAGAL: Nominal tidak boleh nol!');</script>";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO journal_entries (transaction_date, reference_no, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $tgl, $ref, $desc);
            $stmt->execute();
            $journal_id = $conn->insert_id;

            $stmt_item = $conn->prepare("INSERT INTO journal_items (journal_id, account_code, debit, credit) VALUES (?, ?, ?, ?)");

            for($i = 0; $i < count($akun_code); $i++){
                $kd = $akun_code[$i]; $d = floatval($debit[$i]); $k = floatval($kredit[$i]);
                if(!empty($kd)) {
                    $stmt_item->bind_param("isdd", $journal_id, $kd, $d, $k);
                    $stmt_item->execute();
                    
                    // Update Saldo
                    $cek = $conn->query("SELECT normal_balance FROM chart_of_accounts WHERE code = '$kd'")->fetch_assoc();
                    if($cek['normal_balance'] == 'Debit'){
                        $conn->query("UPDATE chart_of_accounts SET balance = balance + $d - $k WHERE code = '$kd'");
                    } else {
                        $conn->query("UPDATE chart_of_accounts SET balance = balance + $k - $d WHERE code = '$kd'");
                    }
                }
            }
            $conn->commit();
            echo "<script>alert('Transaksi berhasil disimpan.'); window.location='journals.php';</script>";
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('ERROR: " . $e->getMessage() . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Umum - Alfamart SIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #151521; }
        .card-panel { background-color: #1e1e2d; border: 1px solid #2b2b40; }
        select, input, textarea { background-color: #151521 !important; color: white !important; border-color: #374151 !important; }
        select option { background-color: #1e1e2d !important; color: white !important; padding: 10px; }
        input[type="date"] { color-scheme: dark; }
        
        /* Modal Animation */
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow-x: hidden; overflow-y: visible !important; }
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
                        <input type="text" name="ref" value="JU-<?php echo date('ymd'); ?>-<?php echo rand(100,999); ?>" class="w-full rounded-lg px-4 py-3 font-mono outline-none" readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Tanggal</label>
                        <input type="date" name="tgl" value="<?php echo date('Y-m-d'); ?>" class="w-full rounded-lg px-4 py-3 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Tipe Jurnal</label>
                        <select name="tipe_jurnal" class="w-full rounded-lg px-4 py-3 outline-none cursor-pointer">
                            <option value="Umum">Jurnal Umum (General)</option>
                            <option value="Penyesuaian">Jurnal Penyesuaian</option>
                        </select>
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Keterangan Utama</label>
                    <input type="text" name="deskripsi_utama" placeholder="Contoh: Pembayaran Gaji..." class="w-full rounded-lg px-4 py-3 outline-none" required>
                </div>

                <div class="border border-gray-700 rounded-xl overflow-hidden mb-6 bg-[#151521]/50">
                    <table class="w-full text-sm" id="jurnalTable">
                        <thead class="bg-[#1e1e2d] text-gray-400 border-b border-gray-700">
                            <tr>
                                <th class="p-4 text-left w-5/12 font-semibold">Nama Akun</th>
                                <th class="p-4 text-left font-semibold">Ket. Baris</th>
                                <th class="p-4 text-right w-32 font-semibold">Debit</th>
                                <th class="p-4 text-right w-32 font-semibold">Kredit</th>
                                <th class="p-4 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800" id="tableBody">
                            <tr class="group hover:bg-gray-800/30 transition">
                                <td class="p-3">
                                    <select name="akun_code[]" class="w-full rounded-lg p-2.5 outline-none" required>
                                        <option value="" disabled selected>-- Pilih Akun --</option>
                                        <?php 
                                        $akun = $conn->query("SELECT code, name FROM chart_of_accounts ORDER BY code ASC");
                                        $options = "";
                                        while($a = $akun->fetch_assoc()) {
                                            $options .= "<option value='".$a['code']."'>[".$a['code']."] ".$a['name']."</option>";
                                        }
                                        echo $options;
                                        ?>
                                    </select>
                                </td>
                                <td class="p-3"><input type="text" name="ket_baris[]" class="w-full bg-transparent border-none outline-none p-2" placeholder="Ket."></td>
                                <td class="p-3"><input type="number" name="debit[]" class="input-angka w-full text-right p-2 rounded outline-none font-mono" placeholder="0" oninput="hitungTotal()" value="0"></td>
                                <td class="p-3"><input type="number" name="kredit[]" class="input-angka w-full text-right p-2 rounded outline-none font-mono" placeholder="0" oninput="hitungTotal()" value="0"></td>
                                <td class="p-3 text-center text-red-500 cursor-pointer" onclick="hapusBaris(this)">x</td>
                            </tr>
                            <tr class="group hover:bg-gray-800/30 transition">
                                <td class="p-3">
                                    <select name="akun_code[]" class="w-full rounded-lg p-2.5 outline-none" required>
                                        <option value="" disabled selected>-- Pilih Akun --</option>
                                        <?php echo $options; ?>
                                    </select>
                                </td>
                                <td class="p-3"><input type="text" name="ket_baris[]" class="w-full bg-transparent border-none outline-none p-2" placeholder="Ket."></td>
                                <td class="p-3"><input type="number" name="debit[]" class="input-angka w-full text-right p-2 rounded outline-none font-mono" placeholder="0" oninput="hitungTotal()" value="0"></td>
                                <td class="p-3"><input type="number" name="kredit[]" class="input-angka w-full text-right p-2 rounded outline-none font-mono" placeholder="0" oninput="hitungTotal()" value="0"></td>
                                <td class="p-3 text-center text-red-500 cursor-pointer" onclick="hapusBaris(this)">x</td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" onclick="tambahBaris()" class="w-full p-3 bg-gray-800/50 hover:bg-gray-800 text-blue-400 text-sm font-medium transition flex items-center justify-center border-t border-gray-700">+ Tambah Baris Baru</button>
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-gray-700">
                    <div class="flex gap-4 mt-1 font-mono text-sm">
                        <div>Db: <span id="totalDebit" class="text-green-400 font-bold">0</span></div>
                        <div>Kr: <span id="totalKredit" class="text-green-400 font-bold">0</span></div>
                        <div id="statusBalance" class="px-2 py-0.5 rounded bg-gray-700 text-gray-400 text-xs">Belum Balance</div>
                    </div>
                    <button type="submit" name="simpan_jurnal" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 rounded-lg text-white font-bold shadow-lg shadow-blue-500/20 transition">Simpan Jurnal</button>
                </div>
            </form>

            <div class="mt-10">
                <h3 class="text-white font-bold text-lg mb-4 flex items-center">
                    <span class="w-2 h-6 bg-blue-500 mr-3 rounded-full"></span>
                    Riwayat 5 Transaksi Terakhir (Klik untuk Detail)
                </h3>
                <div class="card-panel rounded-xl overflow-hidden shadow-lg border border-gray-800">
                    <table class="w-full text-left text-sm text-gray-400">
                        <thead class="bg-[#151521] text-xs uppercase font-bold border-b border-gray-700">
                            <tr>
                                <th class="p-4 w-40">Tanggal</th>
                                <th class="p-4 w-40">No. Ref</th>
                                <th class="p-4">Deskripsi</th>
                                <th class="p-4 text-right">Total Nilai</th>
                                <th class="p-4 text-center w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800 bg-[#1e1e2d]">
                            <?php
                            $log_query = "SELECT j.id, j.transaction_date, j.reference_no, j.description, 
                                          (SELECT SUM(debit) FROM journal_items WHERE journal_id = j.id) as total_nilai
                                          FROM journal_entries j ORDER BY j.id DESC LIMIT 5";
                            $log = $conn->query($log_query);
                            
                            if($log && $log->num_rows > 0){
                                while($l = $log->fetch_assoc()){
                                    // Tambahkan onclick event
                                    echo "<tr class='hover:bg-blue-900/20 transition cursor-pointer group' onclick='showDetail(".$l['id'].")'>";
                                    echo "<td class='p-4 font-mono text-blue-400 group-hover:text-blue-300'>" . date('d/m/Y', strtotime($l['transaction_date'])) . "</td>";
                                    echo "<td class='p-4 font-mono text-gray-300'>" . $l['reference_no'] . "</td>";
                                    echo "<td class='p-4 text-white font-medium'>" . $l['description'] . "</td>";
                                    echo "<td class='p-4 text-right font-mono text-green-400'>Rp " . number_format($l['total_nilai'], 0, ',', '.') . "</td>";
                                    echo "<td class='p-4 text-center'><span class='text-xs bg-gray-700 px-2 py-1 rounded group-hover:bg-blue-600 group-hover:text-white transition'>View</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='p-8 text-center italic text-gray-600'>Belum ada transaksi.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="modalDetail" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/70 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div class="bg-[#1e1e2d] w-full max-w-3xl rounded-xl shadow-2xl border border-gray-700 transform scale-95 transition-transform duration-300" id="modalContent">
            <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center bg-[#151521] rounded-t-xl">
                <div>
                    <h3 class="text-xl font-bold text-white">Detail Transaksi</h3>
                    <p class="text-xs text-blue-400 font-mono mt-1" id="modalRef">REF-XXXXX</p>
                </div>
                <button onclick="closeModal()" class="text-gray-400 hover:text-red-500 transition text-2xl">&times;</button>
            </div>
            
            <div class="p-6">
                <div class="flex justify-between mb-4 text-sm text-gray-400">
                    <div>Tanggal: <span class="text-white font-bold" id="modalDate">-</span></div>
                    <div>Ket: <span class="text-white italic" id="modalDesc">-</span></div>
                </div>

                <div class="border border-gray-700 rounded-lg overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-[#151521] text-gray-400 text-xs uppercase font-bold">
                            <tr>
                                <th class="p-3">Kode Akun</th>
                                <th class="p-3">Nama Akun</th>
                                <th class="p-3 text-right">Debit</th>
                                <th class="p-3 text-right">Kredit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700" id="modalTableBody">
                            </tbody>
                        <tfoot class="bg-[#151521] font-bold text-white">
                            <tr>
                                <td colspan="2" class="p-3 text-right">TOTAL</td>
                                <td class="p-3 text-right text-green-400" id="modalTotalDebit">0</td>
                                <td class="p-3 text-right text-green-400" id="modalTotalKredit">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="px-6 py-4 bg-[#151521] rounded-b-xl flex justify-end">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm transition">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        const optionsAkun = `<?php echo $options; ?>`;

        // --- FUNGSI MODAL ---
        function showDetail(id) {
            const modal = document.getElementById('modalDetail');
            const content = document.getElementById('modalContent');
            
            // Fetch Data dari Server
            fetch(`get_journal_detail.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    // Isi Header
                    document.getElementById('modalRef').innerText = data.header.reference_no;
                    document.getElementById('modalDate').innerText = data.header.transaction_date;
                    document.getElementById('modalDesc').innerText = data.header.description;

                    // Isi Tabel
                    const tbody = document.getElementById('modalTableBody');
                    tbody.innerHTML = '';
                    let totalDebit = 0;
                    let totalKredit = 0;

                    data.items.forEach(item => {
                        let debit = Number(item.debit);
                        let kredit = Number(item.credit);
                        totalDebit += debit;
                        totalKredit += kredit;

                        let row = `
                            <tr class="hover:bg-white/5">
                                <td class="p-3 font-mono text-blue-400 text-xs">${item.account_code}</td>
                                <td class="p-3 text-gray-300">${item.account_name}</td>
                                <td class="p-3 text-right font-mono text-gray-400">${debit > 0 ? 'Rp ' + debit.toLocaleString('id-ID') : '-'}</td>
                                <td class="p-3 text-right font-mono text-gray-400">${kredit > 0 ? 'Rp ' + kredit.toLocaleString('id-ID') : '-'}</td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });

                    document.getElementById('modalTotalDebit').innerText = 'Rp ' + totalDebit.toLocaleString('id-ID');
                    document.getElementById('modalTotalKredit').innerText = 'Rp ' + totalKredit.toLocaleString('id-ID');

                    // Tampilkan Modal dengan Animasi
                    modal.classList.remove('hidden');
                    // Timeout kecil agar transisi opacity jalan
                    setTimeout(() => {
                        modal.classList.remove('opacity-0');
                        content.classList.remove('scale-95');
                        content.classList.add('scale-100');
                    }, 10);
                });
        }

        function closeModal() {
            const modal = document.getElementById('modalDetail');
            const content = document.getElementById('modalContent');
            
            modal.classList.add('opacity-0');
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // --- FUNGSI INPUT JURNAL ---
        function tambahBaris() {
            const tableBody = document.getElementById('tableBody');
            const row = `
            <tr class="group hover:bg-gray-800/30 transition">
                <td class="p-3">
                    <select name="akun_code[]" class="w-full rounded-lg p-2.5 outline-none" required>
                        <option value="" disabled selected>-- Pilih Akun --</option>
                        ${optionsAkun}
                    </select>
                </td>
                <td class="p-3"><input type="text" name="ket_baris[]" class="w-full bg-transparent border-none outline-none p-2" placeholder="Ket."></td>
                <td class="p-3"><input type="number" name="debit[]" class="input-angka w-full text-right p-2 rounded outline-none font-mono" placeholder="0" oninput="hitungTotal()" value="0"></td>
                <td class="p-3"><input type="number" name="kredit[]" class="input-angka w-full text-right p-2 rounded outline-none font-mono" placeholder="0" oninput="hitungTotal()" value="0"></td>
                <td class="p-3 text-center text-red-500 cursor-pointer" onclick="hapusBaris(this)">x</td>
            </tr>`;
            tableBody.insertAdjacentHTML('beforeend', row);
        }

        function hapusBaris(btn) {
            if(document.querySelectorAll('#tableBody tr').length > 2){
                btn.closest('tr').remove();
                hitungTotal();
            } else {
                alert("Minimal harus ada 2 baris!");
            }
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