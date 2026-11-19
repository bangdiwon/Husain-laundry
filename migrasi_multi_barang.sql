-- HANYA jika database lama sudah berisi data dan ingin dipertahankan.
-- (Untuk instalasi baru cukup import database.sql.) Jalankan sekali di phpMyAdmin > tab SQL.
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
INSERT INTO transaksi_item (transaksi_id, layanan_id, nama, harga_satuan, jumlah, subtotal)
  SELECT t.id, t.layanan_id, l.nama, t.harga_satuan, t.jumlah, ROUND(t.harga_satuan * t.jumlah)
  FROM transaksi t JOIN layanan l ON l.id = t.layanan_id;
-- Nama kunci asing (transaksi_ibfk_2) bisa berbeda; lihat di tab Struktur > Relation view bila error.
ALTER TABLE transaksi DROP FOREIGN KEY transaksi_ibfk_2;
ALTER TABLE transaksi DROP COLUMN layanan_id, DROP COLUMN jumlah, DROP COLUMN harga_satuan;
