<?php
// Check permission
if (!hasRole(['admin', 'manager'])) {
    require_once __DIR__ . '/../../../pages/errors/403.php';
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_obat = cleanInput($_POST['kode_obat']);
    $nama_obat = cleanInput($_POST['nama_obat']);
    $kategori = cleanInput($_POST['kategori']);
    $deskripsi = cleanInput($_POST['deskripsi']);
    $satuan = cleanInput($_POST['satuan']);
    $harga = !empty($_POST['harga']) ? (int)$_POST['harga'] : 0;
    $stok = !empty($_POST['stok']) ? (int)$_POST['stok'] : 0;
    $minimal_stok = !empty($_POST['minimal_stok']) ? (int)$_POST['minimal_stok'] : 0;
    $expired_date = !empty($_POST['expired_date']) ? cleanInput($_POST['expired_date']) : null;

    // Validation
    if (empty($kode_obat) || empty($nama_obat)) {
        $error = 'Kode obat dan nama obat wajib diisi!';
    } else {
        // Check if obat with same kode_obat already exists
        $stmt = $pdo->prepare("SELECT id FROM obat WHERE kode_obat = ?");
        $stmt->execute([$kode_obat]);

        if ($stmt->fetch()) {
            $error = 'Kode obat sudah digunakan!';
        } else {
            // Insert obat into database
            $stmt = $pdo->prepare("
                INSERT INTO obat (kode_obat, nama_obat, kategori, deskripsi, satuan, harga, stok, minimal_stok, expired_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if ($stmt->execute([$kode_obat, $nama_obat, $kategori, $deskripsi, $satuan, $harga, $stok, $minimal_stok, $expired_date])) {
                $obatId = $pdo->lastInsertId();
                logActivity($_SESSION['user_id'], 'create_obat', "Created new obat: $nama_obat");
                setAlert('success', 'Obat berhasil ditambahkan!');
                redirect('index.php?page=master-obat');
            } else {
                $error = 'Gagal menambahkan obat!';
            }
        }
    }
}

// Get all kategori for dropdown
$kategori_list = $pdo->query("SELECT DISTINCT kategori FROM obat WHERE kategori IS NOT NULL ORDER BY kategori")->fetchAll();
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="index.php?page=master-obat" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Tambah Obat Baru</h1>
            <p class="text-gray-500 mt-1">Tambahkan obat ke sistem</p>
        </div>
    </div>
</div>

<?php if ($error): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-4">
    <?php echo $error; ?>
</div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm p-6">
    <form method="POST" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kode Obat *</label>
                <input type="text" name="kode_obat" required
                       value="<?php echo isset($_POST['kode_obat']) ? htmlspecialchars($_POST['kode_obat']) : ''; ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Obat *</label>
                <input type="text" name="nama_obat" required
                       value="<?php echo isset($_POST['nama_obat']) ? htmlspecialchars($_POST['nama_obat']) : ''; ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                <select name="kategori"
                        class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                    <option value="">Pilih Kategori</option>
                    <?php foreach ($kategori_list as $kategori_item): ?>
                        <option value="<?php echo $kategori_item['kategori']; ?>"
                                <?php echo (isset($_POST['kategori']) && $_POST['kategori'] === $kategori_item['kategori']) ? 'selected' : ''; ?>>
                            <?php echo ucfirst($kategori_item['kategori']); ?>
                        </option>
                    <?php endforeach; ?>
                    <!-- Default options if no records exist yet -->
                    <option value="Obat Keras" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] === 'Obat Keras') ? 'selected' : ''; ?>>Obat Keras</option>
                    <option value="Obat Bebas" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] === 'Obat Bebas') ? 'selected' : ''; ?>>Obat Bebas</option>
                    <option value="Obat Bebas Terbatas" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] === 'Obat Bebas Terbatas') ? 'selected' : ''; ?>>Obat Bebas Terbatas</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Satuan</label>
                <input type="text" name="satuan"
                       value="<?php echo isset($_POST['satuan']) ? htmlspecialchars($_POST['satuan']) : ''; ?>"
                       placeholder="Tablet, Kapsul, Botol, dll"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Harga</label>
                <input type="number" name="harga" min="0"
                       value="<?php echo isset($_POST['harga']) ? htmlspecialchars($_POST['harga']) : '0'; ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Stok</label>
                <input type="number" name="stok" min="0"
                       value="<?php echo isset($_POST['stok']) ? htmlspecialchars($_POST['stok']) : '0'; ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Minimal Stok</label>
                <input type="number" name="minimal_stok" min="0"
                       value="<?php echo isset($_POST['minimal_stok']) ? htmlspecialchars($_POST['minimal_stok']) : '0'; ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Kadaluarsa</label>
                <input type="date" name="expired_date"
                       value="<?php echo isset($_POST['expired_date']) ? htmlspecialchars($_POST['expired_date']) : ''; ?>"
                       class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
            <textarea name="deskripsi" rows="4"
                      class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
        </div>

        <div class="flex space-x-3 pt-4 border-t">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors">
                Simpan Obat
            </button>
            <a href="index.php?page=master-obat" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-6 rounded-xl transition-colors inline-block">
                Batal
            </a>
        </div>
    </form>
</div>