-- ==========================================
-- Table: obat
-- ==========================================
CREATE TABLE IF NOT EXISTS obat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_obat VARCHAR(20) NOT NULL UNIQUE,
    nama_obat VARCHAR(100) NOT NULL,
    kategori VARCHAR(50),
    deskripsi TEXT,
    satuan VARCHAR(20),
    harga INT DEFAULT 0,
    stok INT DEFAULT 0,
    minimal_stok INT DEFAULT 0,
    expired_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kode_obat (kode_obat),
    INDEX idx_nama_obat (nama_obat),
    INDEX idx_kategori (kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for testing
INSERT INTO obat (kode_obat, nama_obat, kategori, deskripsi, satuan, harga, stok, minimal_stok, expired_date) VALUES
('OBT001', 'Paracetamol 500mg', 'Obat Keras', 'Obat untuk menurunkan demam dan meredakan nyeri', 'Tablet', 1500, 100, 10, '2026-12-31'),
('OBT002', 'Amoxicillin 500mg', 'Obat Keras', 'Antibiotik untuk mengobati berbagai infeksi bakteri', 'Kapsul', 3000, 50, 5, '2025-11-30'),
('OBT003', 'Ibuprofen 400mg', 'Obat Keras', 'Obat antiinflamasi non-steroid untuk meredakan nyeri', 'Tablet', 2000, 75, 8, '2026-06-30'),
('OBT004', 'PCT Sirup', 'Obat Bebas', 'Paracetamol sirup untuk anak-anak', 'Botol', 25000, 20, 3, '2026-03-15'),
('OBT005', 'Betadine', 'Obat Bebas', 'Antiseptik untuk membersihkan luka', 'Botol', 18000, 30, 5, '2027-01-20');

-- View: obat dengan informasi stok
CREATE OR REPLACE VIEW v_obat_stok AS
SELECT
    id,
    kode_obat,
    nama_obat,
    kategori,
    deskripsi,
    satuan,
    harga,
    stok,
    minimal_stok,
    expired_date,
    CASE 
        WHEN stok <= minimal_stok AND stok > 0 THEN 'Stok Menipis'
        WHEN stok = 0 THEN 'Stok Habis'
        ELSE 'Stok Aman'
    END as status_stok,
    created_at,
    updated_at
FROM obat
ORDER BY nama_obat;