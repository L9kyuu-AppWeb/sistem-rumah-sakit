<?php
// Check permission
if (!hasRole(['admin', 'manager'])) {
    require_once __DIR__ . '/../../../pages/errors/403.php';
    exit;
}

$obatId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$obatId) {
    redirect('index.php?page=master-obat');
}

// Get obat data
$stmt = $pdo->prepare("SELECT * FROM obat WHERE id = ?");
$stmt->execute([$obatId]);
$obat = $stmt->fetch();

if (!$obat) {
    setAlert('error', 'Obat tidak ditemukan!');
    redirect('index.php?page=master-obat');
}

// Delete obat
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Delete the obat from database
        $stmt = $pdo->prepare("DELETE FROM obat WHERE id = ?");
        if ($stmt->execute([$obatId])) {
            logActivity($_SESSION['user_id'], 'delete_obat', "Deleted obat: " . $obat['nama_obat']);
            setAlert('success', 'Obat berhasil dihapus!');
        } else {
            setAlert('error', 'Gagal menghapus obat!');
        }
    } catch (Exception $e) {
        setAlert('error', 'Gagal menghapus obat: ' . $e->getMessage());
    }

    redirect('index.php?page=master-obat');
}
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="index.php?page=master-obat" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Hapus Obat</h1>
            <p class="text-gray-500 mt-1">Konfirmasi penghapusan obat</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm p-6">
    <div class="text-center">
        <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <h3 class="mt-2 text-lg font-medium text-gray-800">Konfirmasi Penghapusan</h3>
        <p class="mt-1 text-sm text-gray-500">Apakah Anda yakin ingin menghapus obat di bawah ini?</p>

        <div class="mt-6 bg-gray-50 p-4 rounded-xl">
            <h4 class="text-center mt-3 font-medium text-gray-800"><?php echo htmlspecialchars($obat['nama_obat']); ?></h4>
            <p class="text-center text-sm text-gray-500"><?php echo htmlspecialchars($obat['kategori']); ?> | Kode: <?php echo htmlspecialchars($obat['kode_obat']); ?></p>
            <p class="text-center text-sm text-gray-500 mt-2">Stok: <?php echo $obat['stok']; ?> | Harga: Rp <?php echo number_format($obat['harga'], 0, ',', '.'); ?></p>
        </div>

        <form method="POST" class="mt-6 flex justify-center space-x-3">
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-6 rounded-xl transition-colors">
                Hapus
            </button>
            <a href="index.php?page=master-obat" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-6 rounded-xl transition-colors inline-block">
                Batal
            </a>
        </form>
    </div>
</div>