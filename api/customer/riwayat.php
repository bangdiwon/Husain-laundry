<?php
require '../inc.php';
guard('customer');

$daftar = q("SELECT * FROM transaksi WHERE user_id=? ORDER BY id DESC", [$_SESSION['uid']])->fetchAll();
$total  = array_sum(array_column($daftar, 'total'));

head('Riwayat', 'customer');
?>
<h3 class="mb-3">Riwayat cucian</h3>

<!-- Ringkasan -->
<div class="row g-3 mb-4">
  <div class="col-6">
    <div class="card p-3">
      <div class="text-muted small">Total transaksi</div>
      <div class="fs-3 fw-bold"><?= count($daftar) ?></div>
    </div>
  </div>

  <div class="col-6">
    <div class="card p-3">
      <div class="text-muted small">Total belanja</div>
      <div class="fs-3 fw-bold" style="color:var(--blue)"><?= rp($total) ?></div>
    </div>
  </div>
</div>

<!-- Daftar transaksi beserta barangnya -->
<?php foreach ($daftar as $t):
    $item = q("SELECT * FROM transaksi_item WHERE transaksi_id=?", [$t['id']])->fetchAll();
?>
  <div class="card p-3 mb-3">

    <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
      <div>
        <b><?= e($t['kode']) ?></b>
        <div class="text-muted small">
          <?= tgl($t['tgl_masuk']) ?>
          <?= $t['diskon_persen'] ? ' | diskon ' . $t['diskon_persen'] . '%' : '' ?>
        </div>
      </div>
      <?= badge($t['status']) ?>
    </div>

    <?php foreach ($item as $i): ?>
      <div class="d-flex justify-content-between small py-1 border-top">
        <span>
          <?= e($i['nama']) ?>
          <span class="text-muted">x<?= (float)$i['jumlah'] ?></span>
        </span>
        <span><?= rp($i['subtotal']) ?></span>
      </div>
    <?php endforeach ?>

    <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-1">
      <a class="btn btn-sm btn-outline-primary" href="struk.php?id=<?= $t['id'] ?>" target="_blank">
        <i class="bi bi-receipt"></i> Lihat struk
      </a>
      <span class="fw-bold fs-5"><?= rp($t['total']) ?></span>
    </div>

  </div>
<?php endforeach ?>

<?php if (!$daftar): ?>
  <div class="card p-5 text-center text-muted">Belum ada transaksi.</div>
<?php endif ?>
<?php foot();
