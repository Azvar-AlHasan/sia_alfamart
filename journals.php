<?php
include 'koneksi.php';

// ==========================================
// A. LOGIKA SIMPAN BARU (INSERT)
// ==========================================
if(isset($_POST['simpan_jurnal'])){
    $ref = $_POST['ref']; $tgl = $_POST['tgl']; $desc = $_POST['deskripsi_utama'];
    $tipe = $_POST['tipe_jurnal'];
    
    $akun_code = isset($_POST['akun_code']) ? $_POST['akun_code'] : [];
    $debit = isset($_POST['debit']) ? $_POST['debit'] : [];
    $kredit = isset($_POST['kredit']) ? $_POST['kredit'] : [];
    
    $total_debit = array_sum($debit); 
    $total_kredit = array_sum($kredit);

    if (count($akun_code) < 2 || $total_debit != $total_kredit || $total_debit == 0) {
        echo "<script>alert('Gagal: Data tidak valid atau tidak balance!');</script>";
    } else {
        $conn->begin_transaction();
        try {
            // Header
            $stmt = $conn->prepare("INSERT INTO journal_entries (transaction_date, reference_no, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $tgl, $ref, $desc);
            $stmt->execute(); 
            $journal_id = $conn->insert_id;

            // Detail & Update Saldo
            $stmt_item = $conn->prepare("INSERT INTO journal_items (journal_id, account_code, debit, credit) VALUES (?, ?, ?, ?)");
            for($i=0; $i<count($akun_code); $i++){
                $kd=$akun_code[$i]; $d=$debit[$i]; $k=$kredit[$i];
                if(!empty($kd)){
                    $stmt_item->bind_param("isdd", $journal_id, $kd, $d, $k);
                    $stmt_item->execute();
                    $cek = $conn->query("SELECT normal_balance FROM chart_of_accounts WHERE code='$kd'")->fetch_assoc();
                    $op = ($cek['normal_balance']=='Debit') ? "balance + $d - $k" : "balance + $k - $d";
                    $conn->query("UPDATE chart_of_accounts SET balance = $op WHERE code='$kd'");
                }
            }
            $conn->commit();
            echo "<script>window.location='journals.php';</script>";
        } catch (Exception $e) { $conn->rollback(); echo "<script>alert('Error: ".$e->getMessage()."');</script>"; }
    }
}

// ==========================================
// B. LOGIKA UPDATE TRANSAKSI (EDIT)
// ==========================================
if(isset($_POST['update_jurnal'])){
    $id_jurnal = $_POST['edit_id'];
    $tgl = $_POST['edit_tgl'];
    $desc = $_POST['edit_desc'];
    
    $akun_code = $_POST['edit_akun_code'];
    $debit = $_POST['edit_debit'];
    $kredit = $_POST['edit_kredit'];

    if (array_sum($debit) != array_sum($kredit)) {
        echo "<script>alert('GAGAL UPDATE: Jurnal tidak balance!'); window.location='journals.php';</script>";
        exit;
    }

    $conn->begin_transaction();
    try {
        // 1. Reverse Saldo Lama
        $old_items = $conn->query("SELECT * FROM journal_items WHERE journal_id='$id_jurnal'");
        while($row = $old_items->fetch_assoc()){
            $kd = $row['account_code']; $d = $row['debit']; $k = $row['credit'];
            $cek = $conn->query("SELECT normal_balance FROM chart_of_accounts WHERE code='$kd'")->fetch_assoc();
            $op = ($cek['normal_balance']=='Debit') ? "balance - $d + $k" : "balance - $k + $d";
            $conn->query("UPDATE chart_of_accounts SET balance = $op WHERE code='$kd'");
        }

        // 2. Hapus & Update Header
        $conn->query("DELETE FROM journal_items WHERE journal_id='$id_jurnal'");
        $conn->query("UPDATE journal_entries SET transaction_date='$tgl', description='$desc' WHERE id='$id_jurnal'");

        // 3. Insert Baru & Update Saldo
        $stmt = $conn->prepare("INSERT INTO journal_items (journal_id, account_code, debit, credit) VALUES (?, ?, ?, ?)");
        for($i=0; $i<count($akun_code); $i++){
            $kd=$akun_code[$i]; $d=$debit[$i]; $k=$kredit[$i];
            if(!empty($kd)){
                $stmt->bind_param("isdd", $id_jurnal, $kd, $d, $k);
                $stmt->execute();
                $cek = $conn->query("SELECT normal_balance FROM chart_of_accounts WHERE code='$kd'")->fetch_assoc();
                $op = ($cek['normal_balance']=='Debit') ? "balance + $d - $k" : "balance + $k - $d";
                $conn->query("UPDATE chart_of_accounts SET balance = $op WHERE code='$kd'");
            }
        }
        $conn->commit();
        echo "<script>alert('Data Berhasil Diupdate!'); window.location='journals.php';</script>";
    } catch (Exception $e) { $conn->rollback(); echo "<script>alert('Gagal Update: ".$e->getMessage()."');</script>"; }
}

// Pagination
$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;
$total_data = $conn->query("SELECT count(*) as c FROM journal_entries")->fetch_assoc()['c'];
$total_pages = ceil($total_data / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jurnal Umum - Alfamart SIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #151521; }
        .card-panel { background-color: #1e1e2d; border: 1px solid #2b2b40; }
        select, input, textarea { background-color: #151521 !important; color: white !important; border-color: #374151 !important; }
        select option { background-color: #1e1e2d !important; color: white !important; padding: 10px; }
        input[type="date"] { color-scheme: dark; }
        input:focus, select:focus { border-color: #3b82f6 !important; outline: 2px solid #3b82f6; }
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow: hidden; }
    </style>
</head>
<body class="text-gray-300 font-sans antialiased">

    <?php include 'sidebar.php'; ?>

    <div class="md:ml-64 min-h-screen flex flex-col transition-all duration-300">
        <header class="h-20 bg-[#1e1e2d] border-b border-gray-800 flex items-center px-8 sticky top-0 z-40">
            <h2 class="text-2xl font-bold text-white">Jurnal Umum</h2>
        </header>

        <main class="p-8 space-y-8">
            
            <form method="POST" action="" class="card-panel rounded-xl p-6 shadow-xl border-t-4 border-blue-600">
                <div class="mb-6"><h3 class="text-lg font-bold text-white">📝 Input Transaksi Baru</h3></div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div><label class="text-xs font-bold text-gray-500 uppercase">No. Ref</label><input type="text" name="ref" value="JU-<?php echo date('ymd'); ?>-<?php echo rand(100,999); ?>" class="w-full rounded-lg px-4 py-2 font-mono outline-none" readonly></div>
                    <div><label class="text-xs font-bold text-gray-500 uppercase">Tanggal</label><input type="date" name="tgl" value="<?php echo date('Y-m-d'); ?>" class="w-full rounded-lg px-4 py-2 outline-none"></div>
                    <div><label class="text-xs font-bold text-gray-500 uppercase">Tipe</label><select name="tipe_jurnal" class="w-full rounded-lg px-4 py-2 outline-none cursor-pointer"><option>Jurnal Umum</option><option>Penyesuaian</option></select></div>
                </div>
                <div class="mb-6"><label class="text-xs font-bold text-gray-500 uppercase">Keterangan</label><input type="text" name="deskripsi_utama" class="w-full rounded-lg px-4 py-2 outline-none" required></div>

                <div class="border border-gray-700 rounded-xl overflow-hidden mb-6 bg-[#151521]/50">
                    <table class="w-full text-sm">
                        <thead class="bg-[#1e1e2d] text-gray-400">
                            <tr><th class="p-3 w-5/12">Akun</th><th class="p-3">Ket. Baris</th><th class="p-3 w-32 text-right">Debit</th><th class="p-3 w-32 text-right">Kredit</th><th class="p-3 w-10"></th></tr>
                        </thead>
                        <tbody id="tableBody">
                            </tbody>
                    </table>
                    <button type="button" onclick="tambahBaris('tableBody')" class="w-full p-2 bg-gray-800/50 hover:bg-gray-800 text-blue-400 text-sm font-medium border-t border-gray-700">+ Tambah Baris</button>
                </div>
                
                <div class="flex justify-between items-center bg-gray-900/50 p-3 rounded-lg border border-gray-700">
                    <div class="flex items-center gap-6 font-mono text-sm">
                        <div class="flex flex-col">
                            <span class="text-xs text-gray-500 uppercase">Total Debit</span>
                            <span id="totalDebit" class="text-white font-bold text-lg">0</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-gray-500 uppercase">Total Kredit</span>
                            <span id="totalKredit" class="text-white font-bold text-lg">0</span>
                        </div>
                        <div id="statusBalance" class="ml-4 px-3 py-1 rounded text-xs font-bold flex items-center bg-gray-700 text-gray-400">
                            Belum Balance
                        </div>
                    </div>
                    <button type="submit" name="simpan_jurnal" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-white font-bold shadow-lg">Simpan</button>
                </div>
            </form>

            <div class="mt-10">
                <div class="flex justify-between items-end mb-4">
                    <h3 class="text-white font-bold text-lg flex items-center"><span class="w-2 h-6 bg-yellow-500 mr-3 rounded-full"></span>Riwayat Transaksi</h3>
                    <div class="text-sm text-gray-500">Hal <?php echo $page; ?> / <?php echo $total_pages; ?></div>
                </div>

                <div class="card-panel rounded-xl overflow-hidden shadow-lg border border-gray-800">
                    <table class="w-full text-left text-sm text-gray-400">
                        <thead class="bg-[#151521] text-xs uppercase font-bold border-b border-gray-700">
                            <tr><th class="p-4 w-32">Tanggal</th><th class="p-4 w-40">No. Ref</th><th class="p-4">Deskripsi</th><th class="p-4 text-right w-40">Total Nilai</th><th class="p-4 text-center w-24">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800 bg-[#1e1e2d]">
                            <?php
                            $log_query = "SELECT j.id, j.transaction_date, j.reference_no, j.description, 
                                          (SELECT SUM(debit) FROM journal_items WHERE journal_id = j.id) as total_nilai
                                          FROM journal_entries j ORDER BY j.id DESC LIMIT $start, $limit";
                            $log = $conn->query($log_query);
                            while($l = $log->fetch_assoc()){
                                echo "<tr class='hover:bg-white/5 transition group'>";
                                echo "<td class='p-4 font-mono'>".date('d/m/Y', strtotime($l['transaction_date']))."</td>";
                                echo "<td class='p-4 font-mono text-blue-400 text-xs'>".$l['reference_no']."</td>";
                                echo "<td class='p-4 text-white'>".$l['description']."</td>";
                                echo "<td class='p-4 text-right font-mono text-green-400 font-bold'>Rp ".number_format($l['total_nilai'],0,',','.')."</td>";
                                echo "<td class='p-4 text-center'><button onclick='openEditModal(".$l['id'].")' class='bg-yellow-500/10 hover:bg-yellow-500 hover:text-black text-yellow-500 px-3 py-1.5 rounded text-xs font-bold transition'>EDIT</button></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <?php if($total_pages > 1): ?>
                    <div class="p-4 border-t border-gray-700 flex justify-center gap-2 bg-[#151521]">
                        <?php for($i=1; $i<=$total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="w-8 h-8 flex items-center justify-center rounded text-xs font-bold <?php echo ($i==$page)?'bg-blue-600 text-white':'bg-gray-800 text-gray-400'; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/80 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <form method="POST" class="bg-[#1e1e2d] w-full max-w-4xl rounded-xl shadow-2xl border border-gray-700 transform scale-95 transition-transform duration-300 flex flex-col max-h-[90vh]" id="modalContent">
            <div class="px-6 py-4 border-b border-gray-700 bg-[#151521] rounded-t-xl flex justify-between items-center">
                <div><h3 class="text-xl font-bold text-white">✏️ Edit Transaksi</h3><p class="text-xs text-blue-400 font-mono mt-1" id="editRefDisplay">REF-XXXXX</p></div>
                <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
            </div>
            
            <div class="p-6 overflow-y-auto custom-scrollbar">
                <input type="hidden" name="edit_id" id="editId">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div><label class="text-xs font-bold text-gray-500 uppercase">Tanggal</label><input type="date" name="edit_tgl" id="editTgl" class="w-full rounded px-3 py-2 outline-none"></div>
                    <div><label class="text-xs font-bold text-gray-500 uppercase">Deskripsi</label><input type="text" name="edit_desc" id="editDesc" class="w-full rounded px-3 py-2 outline-none"></div>
                </div>

                <div class="border border-gray-700 rounded-lg overflow-hidden mb-4">
                    <table class="w-full text-sm">
                        <thead class="bg-[#151521] text-gray-400">
                            <tr><th class="p-3 w-5/12">Akun</th><th class="p-3 text-right w-32">Debit</th><th class="p-3 text-right w-32">Kredit</th><th class="p-3 w-10"></th></tr>
                        </thead>
                        <tbody id="editTableBody" class="divide-y divide-gray-700"></tbody>
                    </table>
                    <button type="button" onclick="tambahBaris('editTableBody', true)" class="w-full p-2 bg-gray-800 hover:bg-gray-700 text-blue-400 text-xs font-bold border-t border-gray-700">+ Tambah Baris</button>
                </div>

                <div class="flex justify-end items-center gap-4 font-mono text-sm bg-gray-900/50 p-2 rounded border border-gray-700">
                    <div class="text-gray-400">Total Debit: <span id="editTotalDebit" class="text-white font-bold">0</span></div>
                    <div class="text-gray-400">Total Kredit: <span id="editTotalKredit" class="text-white font-bold">0</span></div>
                    <div id="editStatusBalance" class="px-2 py-0.5 rounded text-xs font-bold bg-gray-700 text-gray-400">Check</div>
                </div>
            </div>

            <div class="px-6 py-4 bg-[#151521] rounded-b-xl flex justify-end gap-3 border-t border-gray-700">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm">Batal</button>
                <button type="submit" name="update_jurnal" class="px-6 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-bold rounded text-sm shadow-lg">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <script>
        const optionsAkun = `<?php 
            $akun = $conn->query("SELECT code, name FROM chart_of_accounts ORDER BY code ASC");
            while($a = $akun->fetch_assoc()) { echo "<option value='".$a['code']."'>[".$a['code']."] ".$a['name']."</option>"; }
        ?>`;

        function tambahBaris(targetId, isEdit = false) {
            const tbody = document.getElementById(targetId);
            const inputName = isEdit ? "edit_" : "";
            const calcFunc = isEdit ? "hitungTotalEdit()" : "hitungTotal()";
            
            const row = `
            <tr class="group hover:bg-gray-800/30">
                <td class="p-2"><select name="${inputName}akun_code[]" class="w-full p-2 rounded outline-none border border-gray-600 bg-gray-900 text-white">${optionsAkun}</select></td>
                ${isEdit ? '' : '<td class="p-2"><input type="text" name="ket_baris[]" class="w-full bg-transparent border-b border-gray-600 outline-none text-white" placeholder="Ket."></td>'}
                <td class="p-2"><input type="number" name="${inputName}debit[]" class="w-full text-right bg-gray-900 text-white p-2 rounded border border-gray-600" value="0" oninput="${calcFunc}"></td>
                <td class="p-2"><input type="number" name="${inputName}kredit[]" class="w-full text-right bg-gray-900 text-white p-2 rounded border border-gray-600" value="0" oninput="${calcFunc}"></td>
                <td class="p-2 text-center text-red-500 cursor-pointer" onclick="this.closest('tr').remove(); ${calcFunc}">x</td>
            </tr>`;
            tbody.insertAdjacentHTML('beforeend', row);
        }

        // FUNGSI CEK BALANCE UMUM (Agar kode tidak redundant)
        function checkStatus(debit, credit, elementId) {
            const el = document.getElementById(elementId);
            if (debit > 0 && debit === credit) {
                el.innerText = "Balance ✅";
                el.className = "ml-4 px-3 py-1 rounded text-xs font-bold flex items-center bg-green-500/20 text-green-400 border border-green-500/30";
            } else {
                el.innerText = "Tidak Balance ⚠️";
                el.className = "ml-4 px-3 py-1 rounded text-xs font-bold flex items-center bg-red-500/20 text-red-400 border border-red-500/30";
            }
        }

        function hitungTotal() {
            let d = 0, k = 0;
            document.querySelectorAll('#tableBody input[name="debit[]"]').forEach(i => d += Number(i.value));
            document.querySelectorAll('#tableBody input[name="kredit[]"]').forEach(i => k += Number(i.value));
            document.getElementById('totalDebit').innerText = d.toLocaleString();
            document.getElementById('totalKredit').innerText = k.toLocaleString();
            checkStatus(d, k, 'statusBalance');
        }

        function hitungTotalEdit() {
            let d = 0, k = 0;
            document.querySelectorAll('#editTableBody input[name="edit_debit[]"]').forEach(i => d += Number(i.value));
            document.querySelectorAll('#editTableBody input[name="edit_kredit[]"]').forEach(i => k += Number(i.value));
            document.getElementById('editTotalDebit').innerText = d.toLocaleString();
            document.getElementById('editTotalKredit').innerText = k.toLocaleString();
            checkStatus(d, k, 'editStatusBalance');
        }

        function openEditModal(id) {
            const modal = document.getElementById('editModal');
            const content = document.getElementById('modalContent');
            fetch(`get_journal_detail.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('editId').value = data.header.id;
                    document.getElementById('editRefDisplay').innerText = data.header.reference_no;
                    document.getElementById('editTgl').value = data.header.transaction_date;
                    document.getElementById('editDesc').value = data.header.description;
                    const tbody = document.getElementById('editTableBody');
                    tbody.innerHTML = '';
                    data.items.forEach(item => {
                        let sel = optionsAkun.replace(`value='${item.account_code}'`, `value='${item.account_code}' selected`);
                        let row = `<tr><td class="p-2"><select name="edit_akun_code[]" class="w-full p-2 bg-gray-900 border border-gray-600 text-white rounded">${sel}</select></td><td class="p-2"><input type="number" name="edit_debit[]" value="${item.debit}" class="w-full text-right bg-gray-900 border border-gray-600 text-white p-2 rounded" oninput="hitungTotalEdit()"></td><td class="p-2"><input type="number" name="edit_kredit[]" value="${item.credit}" class="w-full text-right bg-gray-900 border border-gray-600 text-white p-2 rounded" oninput="hitungTotalEdit()"></td><td class="p-2 text-center text-red-500 cursor-pointer" onclick="this.closest('tr').remove(); hitungTotalEdit()">x</td></tr>`;
                        tbody.innerHTML += row;
                    });
                    hitungTotalEdit();
                    modal.classList.remove('hidden');
                    document.body.classList.add('modal-active');
                    setTimeout(() => { modal.classList.remove('opacity-0'); content.classList.remove('scale-95'); content.classList.add('scale-100'); }, 10);
                });
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            const content = document.getElementById('modalContent');
            modal.classList.add('opacity-0'); content.classList.remove('scale-100'); content.classList.add('scale-95'); document.body.classList.remove('modal-active');
            setTimeout(() => { modal.classList.add('hidden'); }, 300);
        }

        tambahBaris('tableBody'); tambahBaris('tableBody');
    </script>
</body>
</html>