<?php
require '../inc.php';
guard('customer');

$daftar = q("SELECT * FROM layanan WHERE aktif=1 ORDER BY nama")->fetchAll();

head('Layanan & Harga', 'customer');
?>
<h3 class="mb-1">Layanan & harga</h3>
<p class="text-muted mb-4">
  Pilih layanan saat mengantar cucian. Diskon dihitung otomatis oleh kasir.
</p>

<div class="row g-3">
  <?php foreach ($daftar as $l): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card p-4 h-100">
        <div class="job">

          <div class="jico">
            <i class="bi <?= $l['satuan'] == 'kg' ? 'bi-basket3' : 'bi-tag' ?>"></i>
          </div>

          <div>
            <h5 class="mb-1"><?= e($l['nama']) ?></h5>
            <div class="text-muted small mb-3"><?= e($l['deskripsi']) ?></div>

            <div class="fs-4 fw-bold" style="color:var(--blue)"><?= rp($l['harga']) ?></div>
            <span class="badge rounded-pill bg-light text-dark border">per <?= $l['satuan'] ?></span>
          </div>

        </div>
      </div>
    </div>
  <?php endforeach ?>
</div>
<?php foot();
