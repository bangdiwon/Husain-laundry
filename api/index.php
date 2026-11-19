<?php
require 'inc.php';

// Sudah login: langsung ke dashboard
if (!empty($_SESSION['role'])) {
    go($_SESSION['role'] . '/index.php');
}

$layanan = q("SELECT * FROM layanan WHERE aktif=1 ORDER BY nama")->fetchAll();

$promo = q("SELECT * FROM promo
            WHERE aktif = 1
              AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURDATE())
            ORDER BY diskon_persen DESC
            LIMIT 2")->fetchAll();

// Empat langkah cara kerja: [ikon, judul, keterangan]
$langkah = [
    ['bi-bag-check', 'Antar cucian',       'Bawa cucian ke Husain Laundry, kami catat di sistem.'],
    ['bi-phone',     'Pantau status',      'Lihat tahap cucian kapan saja dari akun Anda.'],
    ['bi-bell',      'Terima notifikasi',  'Kabar masuk otomatis setiap status berubah.'],
    ['bi-box2-heart', 'Ambil cucian',      'Datang saat statusnya Selesai, lalu beri penilaian.'],
];

head('Beranda', null, 'full');
?>

<!-- ============ HERO ============ -->
<section class="hero2">
  <div class="container pt-5">
    <div class="row align-items-center g-5">

      <div class="col-lg-6">
        <h1>Laundry rapi, status cucian bisa Anda pantau dari ponsel.</h1>

        <p class="lead my-4">
          Daftar sekali, lalu lihat cucian Anda berpindah dari Antri, Proses, sampai Selesai.
          Anda langsung diberi tahu saat siap diambil.
        </p>

        <a href="register.php" class="btn btn-sun btn-lg px-4 me-2">Daftar gratis</a>
        <a href="login.php" class="btn btn-outline-light btn-lg px-4">Masuk</a>
      </div>

      <!-- Contoh tampilan status -->
      <div class="col-lg-5 offset-lg-1">
        <div class="mock">
          <div class="d-flex justify-content-between mb-1">
            <b>HL2611150912</b>
            <span class="live"><i class="dot"></i>Live</span>
          </div>

          <div class="text-muted small mb-4">Cuci Setrika x4, Bed Cover x1</div>

          <?= tracker('Proses') ?>

          <div class="alert alert-primary mt-4 mb-0 py-2 small">
            <i class="bi bi-bell-fill me-1"></i>Cucian Anda sedang diproses.
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ============ CARA KERJA ============ -->
<section class="container py-5">
  <h2 class="text-center mb-1">Cara kerjanya</h2>
  <p class="text-center text-muted mb-4">Empat langkah, tanpa perlu bolak-balik menelepon.</p>

  <div class="row g-3">
    <?php foreach ($langkah as $no => [$ikon, $judul, $isi]): ?>
      <div class="col-md-3">
        <div class="card p-4 h-100">
          <div class="ico mb-3"><i class="bi <?= $ikon ?>"></i></div>
          <h5><?= $no + 1 ?>. <?= $judul ?></h5>
          <div class="text-muted"><?= $isi ?></div>
        </div>
      </div>
    <?php endforeach ?>
  </div>
</section>

<!-- ============ LAYANAN & HARGA ============ -->
<section class="container pb-5">
  <h2 class="mb-1">Layanan & harga</h2>
  <p class="text-muted mb-4">Harga transparan, per kilogram atau per satuan.</p>

  <div class="row g-3">
    <?php foreach ($layanan as $l): ?>
      <div class="col-md-4">
        <div class="card p-4 h-100">
          <div class="d-flex gap-3">

            <div class="ico flex-shrink-0"><i class="bi bi-basket3"></i></div>

            <div>
              <h5 class="mb-1"><?= e($l['nama']) ?></h5>
              <div class="text-muted small mb-2"><?= e($l['deskripsi']) ?></div>
              <div class="stat fs-4">
                <?= rp($l['harga']) ?>
                <small class="fs-6 text-muted fw-normal">/ <?= $l['satuan'] ?></small>
              </div>
            </div>

          </div>
        </div>
      </div>
    <?php endforeach ?>
  </div>
</section>

<!-- ============ PROMO ============ -->
<?php if ($promo): ?>
  <section class="container pb-5">
    <div class="row g-3">
      <?php foreach ($promo as $p): ?>
        <div class="col-md-6">
          <div class="card p-4 h-100" style="background:linear-gradient(120deg,#fff7e0,#fff)">
            <div class="d-flex align-items-center gap-3">
              <div class="stat text-warning"><?= $p['diskon_persen'] ?>%</div>
              <div>
                <h5 class="mb-1"><?= e($p['judul']) ?></h5>
                <div class="text-muted"><?= e($p['deskripsi']) ?></div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  </section>
<?php endif ?>

<!-- ============ AJAKAN ============ -->
<section class="container pb-5">
  <div class="card p-5 text-center border-0 text-white"
       style="background:linear-gradient(135deg,#0b2545,#1363df)">
    <h2>Siap cucian tanpa repot?</h2>
    <p class="mb-4" style="color:#cfe0f7">Buat akun dan mulai pantau cucian Anda hari ini.</p>
    <div><a href="register.php" class="btn btn-sun btn-lg px-4">Buat akun</a></div>
  </div>
</section>

<footer class="text-center text-muted small pb-4">
  &copy; <?= date('Y') ?> Husain Laundry. Sistem E-CRM
</footer>
<?php foot();
