-- ============================================================
-- Database E-CRM Husain Laundry (pelanggan TANPA login)
-- Alur: Admin input pesanan > Pelanggan lacak pakai kode pesanan
--       > Pesanan selesai > Pelanggan isi review > Dapat kode promo
--       > Kode promo dipakai di transaksi berikutnya
-- ============================================================
CREATE DATABASE IF NOT EXISTS husain_laundry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE husain_laundry;

-- Akun admin (satu-satunya yang punya login)
CREATE TABLE admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Data pelanggan (dicatat admin, pelanggan tidak punya akun)
CREATE TABLE pelanggan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  no_hp VARCHAR(20) DEFAULT NULL,
  alamat TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Daftar layanan dan harga
CREATE TABLE layanan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  harga INT NOT NULL,
  satuan ENUM('kg','pcs') NOT NULL DEFAULT 'kg',
  deskripsi VARCHAR(255) DEFAULT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1
);

-- Kode promo buatan admin. untuk_review = 1: dibagikan kepada pelanggan yang mengisi review
CREATE TABLE promo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(30) NOT NULL UNIQUE,
  judul VARCHAR(150) NOT NULL,
  deskripsi TEXT,
  diskon_persen INT NOT NULL DEFAULT 0,
  berlaku_sampai DATE DEFAULT NULL,
  untuk_review TINYINT(1) NOT NULL DEFAULT 1,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pesanan. kode = kode pesanan berurutan (HL00001, HL00002, ...) untuk melacak
CREATE TABLE transaksi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(12) NOT NULL UNIQUE,
  pelanggan_id INT NOT NULL,
  promo_id INT DEFAULT NULL,
  diskon_persen INT NOT NULL DEFAULT 0,
  total INT NOT NULL,
  status ENUM('Antri','Proses','Selesai','Ambil') NOT NULL DEFAULT 'Antri',
  catatan VARCHAR(255) DEFAULT NULL,
  tgl_masuk DATETIME DEFAULT CURRENT_TIMESTAMP,
  tgl_selesai DATETIME DEFAULT NULL,
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE RESTRICT,
  FOREIGN KEY (promo_id) REFERENCES promo(id) ON DELETE SET NULL
);

-- Satu pesanan bisa berisi banyak barang / layanan
CREATE TABLE transaksi_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaksi_id INT NOT NULL,
  layanan_id INT DEFAULT NULL,
  nama VARCHAR(100) NOT NULL,
  harga_satuan INT NOT NULL,
  jumlah DECIMAL(8,2) NOT NULL,
  subtotal INT NOT NULL,
  FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
  FOREIGN KEY (layanan_id) REFERENCES layanan(id) ON DELETE SET NULL
);

-- Review pelanggan (satu pesanan hanya satu review).
-- promo_id = kode promo yang diberikan setelah review diisi.
-- promo_dipakai = sudah dipakai di transaksi berikutnya (dipakai_transaksi_id).
CREATE TABLE review (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaksi_id INT NOT NULL UNIQUE,
  pelanggan_id INT NOT NULL,
  r_kebersihan TINYINT NOT NULL,
  r_waktu TINYINT NOT NULL,
  r_pelayanan TINYINT NOT NULL,
  r_harga TINYINT NOT NULL,
  rekomendasi ENUM('Ya','Tidak') NOT NULL,
  komentar TEXT,
  promo_id INT DEFAULT NULL,
  promo_dipakai TINYINT(1) NOT NULL DEFAULT 0,
  dipakai_transaksi_id INT DEFAULT NULL,
  balasan TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE,
  FOREIGN KEY (promo_id) REFERENCES promo(id) ON DELETE SET NULL,
  FOREIGN KEY (dipakai_transaksi_id) REFERENCES transaksi(id) ON DELETE SET NULL
);

-- ============ DATA AWAL ============
-- Login admin: admin@husainlaundry.com / admin123
INSERT INTO admin (nama, email, password) VALUES
('Admin Husain Laundry', 'admin@husainlaundry.com', '$2b$12$.hn7a33CZ8RkK6uU9lT38Ou.VqX4KkE3eyu3ze3MxKLeDDfJLreue');

INSERT INTO layanan (nama, harga, satuan, deskripsi) VALUES
('Cuci Kering Lipat', 6000, 'kg', 'Dicuci, dikeringkan, dilipat rapi'),
('Cuci Setrika', 8000, 'kg', 'Dicuci, dikeringkan, disetrika dan dilipat'),
('Setrika Saja', 5000, 'kg', 'Khusus setrika'),
('Bed Cover', 35000, 'pcs', 'Selimut / bed cover besar'),
('Jas / Blazer', 25000, 'pcs', 'Cuci dan setrika jas'),
('Sepatu', 30000, 'pcs', 'Cuci sepatu bagian luar dan dalam');

INSERT INTO promo (kode, judul, deskripsi, diskon_persen, berlaku_sampai, untuk_review) VALUES
('TERIMAKASIH10', 'Diskon Terima Kasih', 'Hadiah untuk pelanggan yang sudah mengisi review. Berlaku untuk satu kali transaksi.', 10, '2027-12-31', 1);
