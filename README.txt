E-CRM HUSAIN LAUNDRY (PHP + MySQL)
Perancangan Electronic Customer Relationship Management (E-CRM) Berbasis Web
untuk Meningkatkan Pelayanan dan Loyalitas Pelanggan pada Husain Laundry

CARA PASANG
1. Pasang XAMPP/Laragon (PHP 8.0+ dan MySQL).
2. Salin folder husain_laundry ke htdocs (XAMPP) atau www (Laragon).
3. Buka phpMyAdmin > tab Import > pilih database.sql > Go.
4. Jika user/password MySQL berbeda, ubah di baris pertama inc.php.
5. Buka http://localhost/husain_laundry/

AKUN ADMIN : admin@husainlaundry.com / admin123 (ganti setelah login pertama)
PELANGGAN  : daftar lewat halaman Daftar.

NOTIFIKASI WHATSAPP OTOMATIS (Fonnte)
1. Daftar di fonnte.com, tambahkan perangkat, scan QR dengan WhatsApp toko, lalu salin token.
2. Buka inc.php, isi: const WA_TOKEN = 'TOKEN_ANDA';
3. Pastikan nomor WhatsApp pelanggan terisi (format 08xx). Pesan dikirim saat transaksi dibuat dan setiap status berubah.
4. Tanpa token, tombol hijau WA di halaman Transaksi admin tetap bisa dipakai (buka WhatsApp dengan pesan siap kirim).

STRUKTUR KODE
  inc.php            satu pintu masuk semua halaman
  includes/config.php   koneksi database, daftar status, token WhatsApp
  includes/fungsi.php   fungsi bantu (format rupiah, query, flash, dsb.)
  includes/whatsapp.php pengiriman WhatsApp
  includes/layout.php   kerangka tampilan (menu admin, menu pelanggan)
  assets/style.css      seluruh gaya tampilan (termasuk responsif)
  assets/app.js         notifikasi pop-up dan update real-time
  admin/ dan customer/  halaman masing-masing peran

STATUS CUCIAN: Antri > Proses > Selesai > Ambil
Jika database sudah berisi data, jalankan ubah_status.sql sekali di phpMyAdmin.

KEAMANAN
  - Sesi berakhir setelah 30 menit tanpa aktivitas dan hilang saat browser ditutup.
  - Setiap form memakai token CSRF; folder includes/ dan file .sql tidak bisa dibuka lewat URL.
  - Halaman admin/pelanggan selalu meminta login bila belum masuk.
LAPORAN: menu Laporan > Cetak, PDF, atau Excel (.xlsx) untuk transaksi, pelanggan, umpan balik.
