<?php
require '../inc.php';
guard('customer');

$uid = $_SESSION['uid'];

// ---------- Kirim masukan ----------
if ($_POST) {
    $rating = max(1, min(5, (int)$_POST['rating']));      // rating dibatasi 1 sampai 5

    q("INSERT INTO feedback(user_id, transaksi_id, jenis, rating, pesan)
       VALUES(?, ?, ?, ?, ?)",
      [$uid, $_POST['transaksi_id'] ?: null, $_POST['jenis'], $rating, trim($_POST['pesan'])]);

    flash('Terima kasih, masukan Anda sudah terkirim.');
    go('feedback.php');
}

$transaksi = q("SELECT id, kode FROM transaksi WHERE user_id=? ORDER BY id DESC", [$uid])->fetchAll();
$masukan   = q("SELECT * FROM feedback WHERE user_id=? ORDER BY id DESC", [$uid])->fetchAll();

// Pilihan jenis masukan: [nama, ikon]
$jenis = [
    'Saran'   => 'bi-lightbulb',
    'Keluhan' => 'bi-emoji-frown',
    'Pujian'  => 'bi-emoji-smile',
];

head('Feedback', 'customer');
?>
<div class="row g-4">

  <!-- ============ FORM ============ -->
  <div class="col-lg-5">
    <div class="card p-4">
      <h4>Bagaimana pelayanan kami?</h4>
      <p class="text-muted">Saran dan keluhan Anda membantu kami lebih baik.</p>

      <form method="post">

        <!-- Jenis masukan -->
        <div class="btn-group w-100 mb-3">
          <?php foreach ($jenis as $nama => $ikon): ?>
            <input type="radio" class="btn-check" name="jenis"
                   id="jenis<?= $nama ?>" value="<?= $nama ?>"
                   <?= $nama == 'Saran' ? 'checked' : '' ?>>

            <label class="btn btn-outline-primary" for="jenis<?= $nama ?>">
              <i class="bi <?= $ikon ?>"></i> <?= $nama ?>
            </label>
          <?php endforeach ?>
        </div>

        <!-- Rating bintang -->
        <label class="form-label fw-semibold">Beri rating</label>
        <div class="rate mb-3">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" name="rating" id="r<?= $i ?>" value="<?= $i ?>"
                   <?= $i == 5 ? 'checked' : '' ?>>
            <label for="r<?= $i ?>">★</label>
          <?php endfor ?>
        </div>

        <!-- Transaksi terkait -->
        <label class="form-label fw-semibold">Terkait transaksi (opsional)</label>
        <select name="transaksi_id" class="form-select mb-3">
          <option value="">Tidak terkait transaksi</option>
          <?php foreach ($transaksi as $t): ?>
            <option value="<?= $t['id'] ?>"><?= e($t['kode']) ?></option>
          <?php endforeach ?>
        </select>

        <!-- Pesan -->
        <label class="form-label fw-semibold">Pesan</label>
        <textarea name="pesan" rows="4" class="form-control mb-3"
                  placeholder="Ceritakan pengalaman Anda" required></textarea>

        <button class="btn btn-primary w-100 py-2">Kirim masukan</button>

      </form>
    </div>
  </div>

  <!-- ============ RIWAYAT MASUKAN ============ -->
  <div class="col-lg-7">
    <h4 class="mb-3">Masukan Anda</h4>

    <?php foreach ($masukan as $f): ?>
      <div class="card p-3 mb-3">

        <div class="d-flex justify-content-between">
          <b>
            <?= $f['jenis'] ?>
            <span class="text-warning"><?= stars($f['rating']) ?></span>
          </b>
          <span class="badge rounded-pill bg-secondary"><?= $f['status'] ?></span>
        </div>

        <div class="my-2"><?= nl2br(e($f['pesan'])) ?></div>
        <small class="text-muted"><?= tgl($f['created_at']) ?></small>

        <?php if ($f['balasan']): ?>
          <div class="mt-2 p-3 rounded-4" style="background:var(--sky)">
            <b><i class="bi bi-reply-fill"></i> Balasan Husain Laundry</b>
            <div><?= nl2br(e($f['balasan'])) ?></div>
          </div>
        <?php endif ?>

      </div>
    <?php endforeach ?>

    <?php if (!$masukan): ?>
      <div class="card p-5 text-center text-muted">
        Belum ada masukan. Kami senang mendengar dari Anda.
      </div>
    <?php endif ?>
  </div>

</div>
<?php foot();
