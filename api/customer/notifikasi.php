<?php
require '../inc.php';
guard('customer');

$uid = $_SESSION['uid'];

$daftar = q("SELECT * FROM notifikasi WHERE user_id=? ORDER BY id DESC", [$uid])->fetchAll();

// Setelah halaman ini dibuka, semua notifikasi dianggap sudah dibaca
q("UPDATE notifikasi SET dibaca=1 WHERE user_id=?", [$uid]);

head('Notifikasi', 'customer');
?>
<h3 class="mb-3">Notifikasi</h3>

<?php foreach ($daftar as $n): ?>
  <div class="card p-3 mb-2">
    <div class="job">

      <div class="jico" style="width:42px; height:42px; font-size:1.2rem">
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

<?php if (!$daftar): ?>
  <div class="card p-5 text-center text-muted">Belum ada notifikasi.</div>
<?php endif ?>
<?php foot();
