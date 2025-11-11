<?php
// Check permission
if (!hasRole(['admin', 'manager', 'staff'])) {
    require_once __DIR__ . '/../../../pages/errors/403.php';
    exit;
}

$action = isset($_GET['action']) ? cleanInput($_GET['action']) : 'list';

switch ($action) {
    case 'create':
        require_once 'create.php';
        break;
    case 'edit':
        require_once 'edit.php';
        break;
    case 'delete':
        require_once 'delete.php';
        break;
    default:
        // List obat
        $search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
        $kategoriFilter = isset($_GET['kategori']) ? cleanInput($_GET['kategori']) : '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10; // 10 items per page
        $offset = ($page - 1) * $limit;

        // Count total records for pagination
        $countSql = "SELECT COUNT(*) FROM obat WHERE 1=1";
        $countParams = [];

        if ($search) {
            $countSql .= " AND (nama_obat LIKE ? OR kode_obat LIKE ? OR kategori LIKE ?)";
            $countParams = array_merge($countParams, ["%$search%", "%$search%", "%$search%"]);
        }

        if ($kategoriFilter) {
            $countSql .= " AND kategori = ?";
            $countParams[] = $kategoriFilter;
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $totalRecords = $countStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        // Get obat data
        $sql = "SELECT * FROM obat WHERE 1=1";

        if ($search) {
            $sql .= " AND (nama_obat LIKE :search1 OR kode_obat LIKE :search2 OR kategori LIKE :search3)";
        }

        if ($kategoriFilter) {
            $sql .= " AND kategori = :kategori";
        }

        $sql .= " ORDER BY nama_obat ASC LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);

        if ($search) {
            $stmt->bindValue(':search1', "%$search%");
            $stmt->bindValue(':search2', "%$search%");
            $stmt->bindValue(':search3', "%$search%");
        }
        if ($kategoriFilter) {
            $stmt->bindValue(':kategori', $kategoriFilter);
        }
        
        // Bind limit and offset
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $obat_list = $stmt->fetchAll();

        // Get distinct kategori for filter
        $kategori_list = $pdo->query("SELECT DISTINCT kategori FROM obat WHERE kategori IS NOT NULL ORDER BY kategori")->fetchAll();
?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Master Data Obat</h1>
        <p class="text-gray-500 mt-1">Kelola data obat</p>
    </div>
    <?php if (hasRole(['admin', 'manager'])): ?>
    <a href="index.php?page=master-obat&action=create" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors inline-flex items-center space-x-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Tambah Obat</span>
    </a>
    <?php endif; ?>
</div>

<!-- Search & Filter -->
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-col md:flex-row gap-3">
        <input type="hidden" name="page" value="master-obat">
        <input type="hidden" name="action" value="list">
        <div class="flex-1">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Cari nama obat, kode obat, atau kategori..."
                   class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
        </div>
        <select name="kategori" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            <option value="">Semua Kategori</option>
            <?php foreach ($kategori_list as $kategori): ?>
                <option value="<?php echo $kategori['kategori']; ?>" <?php echo $kategoriFilter === $kategori['kategori'] ? 'selected' : ''; ?>>
                    <?php echo ucfirst($kategori['kategori']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded-xl transition-colors">
            Filter
        </button>
        <?php if ($search || $kategoriFilter): ?>
        <a href="index.php?page=master-obat" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-6 py-2 rounded-xl transition-colors inline-flex items-center">
            Reset
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Obat Table -->
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Kode Obat</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Nama Obat</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Kategori</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Harga</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Stok</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Minimal Stok</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (count($obat_list) > 0): ?>
                    <?php foreach ($obat_list as $obat): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($obat['kode_obat']); ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($obat['nama_obat']); ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($obat['kategori']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-600">Rp <?php echo number_format($obat['harga'], 0, ',', '.'); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-600"><?php echo $obat['stok']; ?></td>
                        <td class="px-6 py-4 text-sm text-gray-600"><?php echo $obat['minimal_stok']; ?></td>
                        <td class="px-6 py-4">
                            <?php 
                            if ($obat['stok'] <= 0) {
                                echo '<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">Stok Habis</span>';
                            } elseif ($obat['stok'] <= $obat['minimal_stok']) {
                                echo '<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">Stok Menipis</span>';
                            } else {
                                echo '<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Stok Aman</span>';
                            }
                            ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2">
                                <a href="index.php?page=master-obat&action=edit&id=<?php echo $obat['id']; ?>"
                                   class="text-blue-600 hover:text-blue-800 transition-colors" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <a href="index.php?page=master-obat&action=delete&id=<?php echo $obat['id']; ?>"
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus obat <?php echo htmlspecialchars($obat['nama_obat']); ?>?')"
                                   class="text-red-600 hover:text-red-800 transition-colors" title="Hapus">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                            </svg>
                            <p class="text-lg font-medium">Tidak ada data obat</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalRecords > $limit): ?>
<div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
    <div class="text-sm text-gray-700">
        Menampilkan <span class="font-medium"><?php echo $offset + 1; ?></span> sampai <span class="font-medium"><?php echo min($offset + $limit, $totalRecords); ?></span> dari <span class="font-medium"><?php echo $totalRecords; ?></span> data
    </div>
    
    <div class="flex space-x-2">
        <?php if ($page > 1): ?>
            <a href="index.php?page=master-obat&action=list&search=<?php echo urlencode($search); ?>&kategori=<?php echo urlencode($kategoriFilter); ?>&page=<?php echo $page - 1; ?>"
               class="px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                Sebelumnya
            </a>
        <?php endif; ?>

        <?php
        // Pagination links
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        ?>

        <?php if ($startPage > 1): ?>
            <a href="index.php?page=master-obat&action=list&search=<?php echo urlencode($search); ?>&kategori=<?php echo urlencode($kategoriFilter); ?>&page=1"
               class="px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors
               <?php echo ($page == 1) ? 'bg-blue-50 border-blue-500 text-blue-600' : ''; ?>">
                1
            </a>
            <?php if ($startPage > 2): ?>
                <span class="px-3 py-2 text-sm font-medium text-gray-700">...</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <a href="index.php?page=master-obat&action=list&search=<?php echo urlencode($search); ?>&kategori=<?php echo urlencode($kategoriFilter); ?>&page=<?php echo $i; ?>"
               class="px-3 py-2 rounded-lg border text-sm font-medium transition-colors
               <?php echo ($i == $page) ? 'bg-blue-50 border-blue-500 text-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
                <span class="px-3 py-2 text-sm font-medium text-gray-700">...</span>
            <?php endif; ?>
            <a href="index.php?page=master-obat&action=list&search=<?php echo urlencode($search); ?>&kategori=<?php echo urlencode($kategoriFilter); ?>&page=<?php echo $totalPages; ?>"
               class="px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors
               <?php echo ($page == $totalPages) ? 'bg-blue-50 border-blue-500 text-blue-600' : ''; ?>">
                <?php echo $totalPages; ?>
            </a>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
            <a href="index.php?page=master-obat&action=list&search=<?php echo urlencode($search); ?>&kategori=<?php echo urlencode($kategoriFilter); ?>&page=<?php echo $page + 1; ?>"
               class="px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                Berikutnya
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
        break;
}