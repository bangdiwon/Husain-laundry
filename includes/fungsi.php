<?php
// ============================================================
// FUNGSI BANTU yang dipakai di banyak halaman
// ============================================================

// Tampilkan teks dengan aman (mencegah XSS)
function e($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

// Format rupiah dan tanggal
function rp($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function tgl($waktu) {
    return $waktu ? date('d/m/Y H:i', strtotime($waktu)) : '-';
}

// Jalankan query SQL dengan parameter aman (prepared statement)
function q($sql, $param = []) {
    global $pdo;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($param);

    return $stmt;
}

// Pindah halaman
function go($url) {
    header("Location: $url");
    exit;
}

// Pesan sekali tampil (mis. "Data tersimpan")
// flash('pesan') = simpan pesan, flash() = tampilkan pesan
function flash($pesan = null, $jenis = 'success') {
    if ($pesan) {
        $_SESSION['flash'] = [$pesan, $jenis];
        return;
    }

    if (isset($_SESSION['flash'])) {
        [$pesan, $jenis] = $_SESSION['flash'];
        unset($_SESSION['flash']);

        return "<div class='alert alert-$jenis'>" . e($pesan) . "</div>";
    }
}

// Halaman admin: wajib login
function guard($peran = 'admin') {
    if (($_SESSION['role'] ?? '') !== $peran) {
        flash('Silakan masuk terlebih dahulu.', 'warning');
        go('../login.php');
    }
}

// KEAMANAN SESI ADMIN: dipanggil otomatis di inc.php
//  - sesi habis setelah 30 menit tanpa aktivitas
//  - halaman admin tidak disimpan browser (tombol Back setelah keluar tidak membuka halaman)
//  - setiap form POST admin wajib membawa token CSRF
function amankan_sesi() {
    if (!empty($_SESSION['uid'])) {
        if (time() - ($_SESSION['aktif'] ?? 0) > 1800) {
            session_unset();
            flash('Sesi berakhir karena tidak ada aktivitas. Silakan masuk lagi.', 'warning');
        } else {
            // api.php (pembaruan real-time) tidak boleh memperpanjang sesi
            if (!defined('TANPA_AKTIVITAS')) {
                $_SESSION['aktif'] = time();
            }

            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
        }
    }

    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }

    $kirimForm = $_SERVER['REQUEST_METHOD'] === 'POST';

    if ($kirimForm && !empty($_SESSION['uid'])
        && !hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Permintaan tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

// Label status berwarna
function badge($status) {
    $warna = [
        'Antri'   => 'secondary',
        'Proses'  => 'primary',
        'Selesai' => 'success',
        'Ambil'   => 'dark',
    ];

    return "<span class='badge rounded-pill px-2 py-1 bg-{$warna[$status]}'>"
         . e($status)
         . "</span>";
}

// Tombol hapus dengan konfirmasi
function delform($id) {
    return "<form method='post' class='d-inline' onsubmit=\"return confirm('Hapus data ini?')\">
            <input type='hidden' name='del' value='" . (int)$id . "'>
            <button class='btn btn-sm btn-outline-danger'>Hapus</button></form>";
}

// Bintang rating, mis. stars(4) = ★★★★☆
function stars($n) {
    return str_repeat('★', (int)$n) . str_repeat('☆', 5 - (int)$n);
}

// Menandai option terpilih pada <select>
function sel($a, $b) {
    return $a == $b ? 'selected' : '';
}

// Diskon member 5% jika pelanggan sudah punya 5 transaksi atau lebih
function diskon_member($pelangganId, $kecuali = 0) {
    $jumlah = q("SELECT COUNT(*) FROM transaksi WHERE pelanggan_id=? AND id<>?",
                [$pelangganId, $kecuali])->fetchColumn();

    return $jumlah >= 5 ? 5 : 0;
}

// Potongan SQL: ringkasan barang satu transaksi, mis. "Cuci Setrika x4, Bed Cover x1"
// Pakai di query yang memberi alias tabel transaksi = t
const RINGKAS = "(SELECT GROUP_CONCAT(
                    CONCAT(nama, ' x', TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM jumlah)))
                    SEPARATOR ', ')
                 FROM transaksi_item
                 WHERE transaksi_id = t.id)";

// Kode pesanan berurutan dan mudah diingat: HL00001, HL00002, dst.
function kode_pesanan() {
    $terakhir = q("SELECT COALESCE(MAX(CAST(SUBSTRING(kode, 3) AS UNSIGNED)), 0) FROM transaksi")
                ->fetchColumn();

    return 'HL' . str_pad($terakhir + 1, 5, '0', STR_PAD_LEFT);
}

// Rapikan kode yang diketik pelanggan.
// "HL00001", "hl-00001", "HL1", atau cukup "1" semuanya menjadi HL00001
function normal_kode($teks) {
    $k = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$teks));

    if (preg_match('/^(HL)?(\d{1,6})$/', $k, $cocok)) {
        return 'HL' . str_pad((int)$cocok[2], 5, '0', STR_PAD_LEFT);
    }

    return '';
}

// Alamat file di folder assets lengkap dengan nomor versi (agar CSS/JS baru langsung terpakai).
// Vercel: alamat mutlak /assets/...  |  Localhost: alamat relatif dari folder halaman
function aset($file) {
    global $ROLE;

    if (getenv('VERCEL')) {
        $dasar = '/assets/';
    } else {
        $dasar = $ROLE ? '../assets/' : 'assets/';
    }

    return $dasar . $file . '?v=' . filemtime(__DIR__ . '/../assets/' . $file);
}
