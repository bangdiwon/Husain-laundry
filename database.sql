-- Database E-CRM Husain Laundry
CREATE DATABASE IF NOT EXISTS husain_laundry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE husain_laundry;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  no_hp VARCHAR(20) DEFAULT NULL,
  alamat TEXT,
  role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE layanan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  harga INT NOT NULL,
  satuan ENUM('kg','pcs') NOT NULL DEFAULT 'kg',
  deskripsi VARCHAR(255) DEFAULT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE promo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  deskripsi TEXT,
  diskon_persen INT NOT NULL DEFAULT 0,
  berlaku_sampai DATE DEFAULT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transaksi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  promo_id INT DEFAULT NULL,
  diskon_persen INT NOT NULL DEFAULT 0,
  total INT NOT NULL,
  status ENUM('Antri','Proses','Selesai','Ambil') NOT NULL DEFAULT 'Antri',
  catatan VARCHAR(255) DEFAULT NULL,
  tgl_masuk DATETIME DEFAULT CURRENT_TIMESTAMP,
  tgl_selesai DATETIME DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (promo_id) REFERENCES promo(id) ON DELETE SET NULL
);

-- Satu transaksi bisa berisi banyak barang/layanan
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

CREATE TABLE notifikasi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  pesan VARCHAR(255) NOT NULL,
  dibaca TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE feedback (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  transaksi_id INT DEFAULT NULL,
  jenis ENUM('Saran','Keluhan','Pujian') NOT NULL DEFAULT 'Saran',
  rating TINYINT NOT NULL DEFAULT 5,
  pesan TEXT NOT NULL,
  balasan TEXT,
  status ENUM('Baru','Diproses','Selesai') NOT NULL DEFAULT 'Baru',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE SET NULL
);

-- Data awal. Login admin: admin@husainlaundry.com / admin123
INSERT INTO users (nama,email,password,no_hp,alamat,role) VALUES
('Admin Husain Laundry','admin@husainlaundry.com','$2b$12$.hn7a33CZ8RkK6uU9lT38Ou.VqX4KkE3eyu3ze3MxKLeDDfJLreue','081234567890','Jakarta','admin');

INSERT INTO layanan (nama,harga,satuan,deskripsi) VALUES
('Cuci Kering Lipat',6000,'kg','Dicuci, dikeringkan, dilipat rapi'),
('Cuci Setrika',8000,'kg','Dicuci, dikeringkan, disetrika dan dilipat'),
('Setrika Saja',5000,'kg','Khusus setrika'),
('Bed Cover',35000,'pcs','Selimut / bed cover besar'),
('Jas / Blazer',25000,'pcs','Cuci dan setrika jas'),
('Sepatu',30000,'pcs','Cuci sepatu bagian luar dan dalam');

INSERT INTO promo (judul,deskripsi,diskon_persen,berlaku_sampai) VALUES
('Diskon Pelanggan Baru','Diskon untuk transaksi pertama Anda.',10,'2026-12-31'),
('Member Setia','Setiap pelanggan dengan 5 transaksi atau lebih otomatis mendapat diskon member 5%.',5,NULL);
