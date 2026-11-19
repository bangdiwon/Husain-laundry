<?php
require '../inc.php';
guard('customer');

$uid = $_SESSION['uid'];

// ---------- 1. Data pelanggan ----------
$saya = q("SELECT no_hp FROM users WHERE id=?", [$uid])->fetch();

$aktif = q("SELECT t.*, " . RINGKAS . " ringkas
            FROM transaksi t
            WHERE t.user_id = ? AND t.status <> 'Ambil'
            ORDER BY t.id DESC", [$uid])->fetchAll();

$notif = q("SELECT * FROM notifikasi WHERE user_id=? ORDER BY id DESC LIMIT 4", [$uid])->fetchAll();

$jumlahTransaksi = (int)q("SELECT COUNT(*) FROM transaksi WHERE user_id=?", [$uid])->fetchColumn();

// ---------- 2. Sapaan sesuai jam ----------
$jam = (int)date('G');

if ($jam < 11) {
    $sapa = 'Selamat pagi';
} elseif ($jam < 15) {
    $sapa = 'Selamat siang';
} elseif ($jam < 18) {
    $sapa = 'Selamat sore';
} else {
    $sapa = 'Selamat malam';
}

$namaDepan = explode(' ', $_SESSION['nama'])[0];
$member    = $jumlahTransaksi >= 5;
$progres   = min(100, $jumlahTransaksi * 20);      // 5 transaksi = 100%
$adaWa     = wa_no($saya['no_hp']) !== '';

// Tombol akses cepat: [halaman, ikon, label]
$tombol = [
    ['layanan',  'bi-tags',          'Layanan & harga'],
    ['riwayat',  'bi-clock-history', 'Riwayat cucian'],
    ['promo',    'bi-gift',          'Promo'],
    ['feedback', 'bi-chat-heart',    'Beri masukan'],
];

head('Beranda', 'customer');
?>

<!-- ============ BANNER SAPAAN ============ -->
<div class="card greet p-4 p-md-5 mb-4">
  <div class="row align-items-center g-4">

    <div class="col-md-8">
      <div class="chip mb-3">
        <i class="bi bi-stars"></i>
        <?= $member ? 'Member aktif, diskon 5%' : 'Pelanggan Husain Laundry' ?>
      </div>

      <h2 class="mb-2"><?= $sapa ?>, <?= e($namaDepan) ?></h2>

      <p class="mb-3" style="color:#e6faf9">
        Pantau cucian Anda di sini. Kami kabari lewat notifikasi
        <?= $adaWa ? 'dan WhatsApp' : '' ?> setiap ada perkembangan.
      </p>

      <!-- Progres menuju diskon member -->
      <div class="small mb-1 d-flex justify-content-between" style="max-width:360px">
        <span><?= $member ? 'Diskon member sudah aktif' : 'Menuju diskon member 5%' ?></span>
        <span><?= min($jumlahTransaksi, 5) ?>/5</span>
      </div>
      <div class="progress" style="height:10px; max-width:360px; background:rgba(255,255,255,.25)">
        <div class="progress-bar bg-warning" style="width:<?= $progres ?>%"></div>
      </div>
    </div>

    <!-- Ilustrasi mesin cuci -->
    <div class="col-md-4 text-center d-none d-md-block">
      <svg viewBox="0 0 120 130" width="150">
        <rect x="10" y="5" width="100" height="120" rx="18" fill="#fff"/>
        <circle cx="60" cy="74" r="35" fill="#cdeff0"/>
        <circle cx="60" cy="74" r="26" fill="#0f9d9a" opacity=".4"/>
        <circle cx="52" cy="66" r="6" fill="#fff" opacity=".7"/>
        <circle cx="30" cy="24" r="5" fill="#0f9d9a"/>
        <rect x="45" y="20" width="44" height="8" rx="4" fill="#e2f5f4"/>
      </svg>
    </div>

  </div>
</div>

<!-- ============ AKSES CEPAT ============ -->
<div class="row g-3 mb-4">
  <?php foreach ($tombol as [$halaman, $ikon, $label]): ?>
    <div class="col-6 col-md-3">
      <a href="<?= $halaman ?>.php" class="card tile h-100">
        <div class="jico mx-auto mb-2"><i class="bi <?= $ikon ?>"></i></div>
        <div class="fw-semibold"><?= $label ?></div>
      </a>
    </div>
  <?php endforeach ?>
</div>

<?php if (!$adaWa): ?>
  <div class="alert alert-warning">
    <i class="bi bi-whatsapp me-1"></i>
    Isi nomor WhatsApp di <a href="profil.php" class="alert-link">profil</a>
    agar kabar cucian dikirim ke WhatsApp Anda.
  </div>
<?php endif ?>

<!-- ============ BAGIAN REAL-TIME (diperbarui otomatis) ============ -->
<div id="live">
  <div class="row g-4">

    <!-- Cucian aktif -->
    <div class="col-lg-8">
      <h5 class="mb-3">Cucian Anda saat ini</h5>

      <?php if (!$aktif): ?>
        <div class="card p-5 text-center text-muted">
          <i class="bi bi-basket3 fs-1" style="color:var(--blue)"></i>
          <h5 class="mt-2 text-dark">Belum ada cucian aktif</h5>
          <div>Antar cucian ke Husain Laundry. Statusnya langsung muncul di sini.</div>
        </div>
      <?php endif ?>

      <?php foreach ($aktif as $t): ?>
        <div class="card p-4 mb-3">

          <div class="job mb-4">
            <div class="jico"><i class="bi bi-basket3"></i></div>

            <div class="flex-grow-1">
              <div class="d-flex justify-content-between flex-wrap gap-2">
                <b class="fs-5"><?= e($t['ringkas']) ?></b>
                <?= badge($t['status']) ?>
              </div>

              <div class="text-muted small">
                <?= e($t['kode']) ?> | Masuk <?= tgl($t['tgl_masuk']) ?>
              </div>

              <div class="fw-semibold mt-1">
                <?= rp($t['total']) ?>
                <a href="struk.php?id=<?= $t['id'] ?>" target="_blank"
                   class="small ms-2 fw-normal">
                  <i class="bi bi-receipt"></i> Lihat struk
                </a>
              </div>
            </div>
          </div>

          <?= tracker($t['status']) ?>

          <?php if ($t['status'] == 'Selesai'): ?>
            <div class="alert alert-success mt-4 mb-0">
              <i class="bi bi-check-circle-fill me-1"></i>
              Cucian sudah selesai. Silakan diambil.
            </div>
          <?php endif ?>

        </div>
      <?php endforeach ?>
    </div>

    <!-- Kabar terbaru -->
    <div class="col-lg-4">
      <h5 class="mb-3">Kabar terbaru</h5>

      <?php foreach ($notif as $n): ?>
        <div class="card p-3 mb-2">
          <div class="job">
            <div class="jico" style="width:40px; height:40px; font-size:1.1rem">
              <i class="bi bi-bell"></i>
            </div>
            <div>
              <div><?= e($n['pesan']) ?></div>
              <small class="text-muted"><?= tgl($n['created_at']) ?></small>
              <?php if (!$n['dibaca']): ?>
                <span class="badge bg-warning text-dark">Baru</span>
              <?php endif ?>
            </div>
          </div>
        </div>
      <?php endforeach ?>

      <?php if (!$notif): ?>
        <div class="text-muted">Belum ada kabar.</div>
      <?php endif ?>

      <a href="notifikasi.php" class="small">Lihat semua</a>
    </div>

  </div>
</div>
<?php foot();
