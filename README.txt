E-CRM HUSAIN LAUNDRY (PHP + MySQL)
Perancangan Electronic Customer Relationship Management (E-CRM) Berbasis Web
untuk Meningkatkan Pelayanan dan Loyalitas Pelanggan pada Husain Laundry

ALUR SISTEM (pelanggan TANPA login, hanya admin yang login)
  1. Admin mencatat pesanan (banyak barang sekaligus) -> sistem membuat KODE PESANAN berurutan: HL00001, HL00002, dst.
  2. Pelanggan menerima struk (dan WhatsApp) berisi kode pesanan
  3. Pelanggan membuka website -> scroll ke "Lacak Pesanan" -> memasukkan kode -> melihat status
     Antri > Proses > Selesai > Ambil (halaman memperbarui diri otomatis)
  4. Saat status Selesai, muncul tombol "Isi review & dapatkan kode promo"
  5. Pelanggan menjawab pertanyaan review -> sistem memberi KODE PROMO buatan admin
     (tanpa mengisi review = tidak mendapat kode promo)
  6. Pada transaksi berikutnya, admin memasukkan kode promo itu. Kode hanya sah untuk
     pelanggan yang menerimanya dan hanya bisa dipakai satu kali.

CARA PASANG
  1. Pasang XAMPP/Laragon (PHP 8.0+ dan MySQL), salin folder husain_laundry ke htdocs.
  2. phpMyAdmin > tab SQL > jalankan: DROP DATABASE IF EXISTS husain_laundry;
  3. Tab Import > pilih database.sql > Go.
  4. Atur includes/config.php: koneksi database, URL_SITUS, alamat/telepon/jam buka usaha, token WhatsApp.
  5. Buka http://localhost/husain_laundry/   (website pelanggan)
     Login admin: http://localhost/husain_laundry/login.php
     Akun admin : admin@husainlaundry.com / admin123 (ganti setelah login pertama)

TABEL DATABASE
  admin, pelanggan, layanan, promo, transaksi, transaksi_item, review

STRUKTUR KODE
  inc.php               satu pintu masuk semua halaman
  index.php             website pelanggan: beranda, tentang, layanan, lacak pesanan, review, kontak
  login.php, logout.php login admin
  struk.php             struk untuk pelanggan (lewat kode pesanan)
  lacak_api.php         pembaruan status real-time di halaman lacak
  api.php               pembaruan real-time panel admin
  includes/             config, fungsi, whatsapp, layout, lacak (review & promo), struk, laporan
  assets/               style.css dan app.js
  admin/                dashboard, pelanggan, transaksi, layanan, promo, feedback (review), laporan, export, struk

KEAMANAN
  - Kode pesanan berurutan (mudah dilacak); percobaan lacak dibatasi (20x per 10 menit) dan nama disamarkan.
  - Nama pelanggan di halaman publik disamarkan.
  - Sesi admin berakhir setelah 30 menit tanpa aktivitas; form admin memakai token CSRF.
  - Folder includes/ dan file .sql tidak bisa dibuka lewat URL.

NOTIFIKASI WHATSAPP (opsional, Fonnte): isi WA_TOKEN di includes/config.php.
  Tanpa token, tombol hijau WA di halaman Transaksi admin tetap bisa dipakai.
LAPORAN: menu Laporan > Cetak, PDF, atau Excel (.xlsx) untuk transaksi, pelanggan, dan review.

VERCEL (lihat vercel.json dan api/router.php)
  Jangan menulis password database / kunci di kode. Isi di Vercel > Settings > Environment Variables:
    DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, APP_KEY (teks acak panjang)
    (opsional) WA_TOKEN, URL_SITUS (domain utama, mis. https://nama-anda.vercel.app)
  Login admin: alamat-website/login.php (tidak ada tombol login di halaman publik).
