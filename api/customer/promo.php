<?php
require '../inc.php';
guard('customer');

$daftar = q("SELECT * FROM promo
             WHERE aktif = 1
               AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURDATE())
             ORDER BY id DESC")->fetchAll();

head('Promo', 'customer');
?>
<h3 class="mb-1">Promo & diskon member</h3>
<p class="text-muted mb-4">Tunjukkan promo ke kasir saat mengantar cucian.</p>

<div class="row g-3">
  <?php foreach ($daftar as $p): ?>
    <div class="col-lg-6">
      <div class="card coupon">

        <div class="pct"><?= $p['diskon_persen'] ?>%</div>

        <div class="body">
          <h5><?= e($p['judul']) ?></h5>
          <div class="text-muted mb-2"><?= nl2br(e($p['deskripsi'])) ?></div>

          <small class="text-muted">
            <i class="bi bi-calendar-event me-1"></i>
            <?php if ($p['berlaku_sampai']): ?>
              Sampai <?= date('d/m/Y', strtotime($p['berlaku_sampai'])) ?>
            <?php else: ?>
              Tanpa batas waktu
            <?php endif ?>
          </small>
        </div>

      </div>
    </div>
  <?php endforeach ?>
</div>

<?php if (!$daftar): ?>
  <div class="card p-5 text-center text-muted">Belum ada promo aktif. Cek lagi nanti ya.</div>
<?php endif ?>
<?php foot();
