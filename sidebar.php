<aside class="fixed inset-y-0 left-0 w-64 bg-[#1e1e2d] border-r border-gray-800 z-50 flex flex-col transition-transform duration-300 transform md:translate-x-0 -translate-x-full">
    
    <div class="h-20 flex items-center justify-center border-b border-gray-800">
        <h1 class="text-2xl font-bold tracking-wider">
            <span class="text-[#d7111b]">Alfa</span><span class="text-[#0056b3]">mart</span>
        </h1>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <p class="text-xs text-gray-500 uppercase font-bold pl-3 mb-2 tracking-wider">Menu Utama</p>
        
        <a href="index.php" class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
            <span class="text-lg mr-3">📊</span> 
            <span class="font-medium">Dashboard</span>
        </a>
        
        <a href="accounts.php" class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'accounts.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
            <span class="text-lg mr-3">🏦</span> 
            <span class="font-medium">Data Akun</span>
        </a>

        <a href="journals.php" class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'journals.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
            <span class="text-lg mr-3">📝</span> 
            <span class="font-medium">Jurnal Umum</span>
        </a>
       
        <a href="ledger.php" class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'ledger.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
            <span class="text-lg mr-3">📒</span> 
            <span class="font-medium">Buku Besar</span>
        </a>

        <a href="cash_flow.php" class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'cash_flow.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
    <span class="text-lg mr-3">🌊</span> 
    <span class="font-medium">Arus Kas</span>
        </a>

        <div class="pt-6">
            <p class="text-xs text-gray-500 uppercase font-bold pl-3 mb-2 tracking-wider">Laporan</p>
            <a href="profit_loss.php" class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'profit_loss.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <span class="text-lg mr-3">📉</span> 
                <span class="font-medium">Laba Rugi</span>
            </a>

            <a href="equity.php" class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'equity.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
    <span class="text-lg mr-3">💹</span> 
    <span class="font-medium">Perubahan Ekuitas</span>
            </a>

            <a href="balance_sheet.php" class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 <?php echo (basename($_SERVER['PHP_SELF']) == 'balance_sheet.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
    <span class="text-lg mr-3">⚖️</span> 
    <span class="font-medium">Neraca</span>
            </a>
            
        </div>
    </nav>

    <div class="p-4 border-t border-gray-800 bg-[#151521]">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold shadow-md">K5</div>
            <div>
                <p class="text-sm font-semibold text-white">Kelompok 5</p>
                <p class="text-xs text-gray-500">Administrator</p>
            </div>
        </div>
    </div>
</aside>