<?php
require '../inc.php';
guard('admin');

// Teks notifikasi untuk setiap perubahan status
function pesan_status($t) {
    return [
        'Antri'   => "Cucian {$t['kode']} sudah kami terima dan masuk antrian.",
        'Proses'  => "Cucian {$t['kode']} sedang kami proses (dicuci dan disetrika).",
        'Selesai' => "Cucian {$t['kode']} sudah selesai dan bisa diambil. "
                   . "Total tagihan " . rp($t['total']) . ".",

        'Ambil'   => "Cucian {$t['kode']} sudah diambil. "
                   . "Terima kasih, mohon beri penilaian di menu Feedback.",
    ][$t['status']];
}

function info_wa($berhasil) {
    if (WA_TOKEN === '') return '';
    if ($berhasil) {
        return ' WhatsApp terkirim.';
    }

    return ' WhatsApp gagal terkirim, gunakan tombol WA di baris transaksi.';
}

// Baca daftar barang dari form: layanan_id[] dan jumlah[] (baris kosong diabaikan)
function ambil_item() {
    $item = [];
    foreach ($_POST['layanan_id'] ?? [] as $i => $layananId) {
        $jumlah  = (float)str_replace(',', '.', $_POST['jumlah'][$i] ?? 0);
        $layanan = $layananId ? q("SELECT * FROM layanan WHERE id=?", [$layananId])->fetch() : null;
        if ($layanan && $jumlah > 0) {
            $item[] = [
                'layanan'  => $layanan,
                'jumlah'   => $jumlah,
                'subtotal' => (int)round($layanan['harga'] * $jumlah),
            ];
        }
    }
    return $item;
}

// ---------- 1. HAPUS ----------
if (isset($_POST['del'])) {
    q("DELETE FROM transaksi WHERE id=?", [$_POST['del']]);
    flash('Transaksi dihapus.');
    go('transaksi.php');
}

// ---------- 2. UBAH STATUS + beri tahu pelanggan ----------
if (isset($_POST['ubah_status'])) {
    $t = q("SELECT * FROM transaksi WHERE id=?", [$_POST['id']])->fetch();
    if ($t && in_array($_POST['status'], STATUS)) {
        q("UPDATE transaksi
           SET status = ?, tgl_selesai = IF(? = 'Selesai', NOW(), tgl_selesai)
           WHERE id = ?",
          [$_POST['status'], $_POST['status'], $t['id']]);
        $t['status'] = $_POST['status'];
        $terkirim = notif($t['user_id'], pesan_status($t), true);
        flash("Status {$t['kode']} diubah menjadi {$t['status']} dan pelanggan diberi tahu."
              . info_wa($terkirim));
    }
    go('transaksi.php');
}

// ---------- 3. SIMPAN (baru / edit) dengan banyak barang ----------
if (isset($_POST['simpan'])) {
    $id     = (int)$_POST['id'];
    $userId = (int)$_POST['user_id'];
    $item   = ambil_item();

    if (!$item) {
        flash('Tambahkan minimal satu barang dengan jumlah lebih dari 0.', 'danger');
        go('transaksi.php' . ($id ? "?edit=$id" : ''));
    }

    $promo = null;
    if ($_POST['promo_id']) {
        $promo = q("SELECT * FROM promo
                    WHERE id = ? AND aktif = 1
                      AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURDATE())",
                   [$_POST['promo_id']])->fetch();
    }

    // Total = jumlah semua barang, dikurangi diskon promo + diskon member
    $subtotal = array_sum(array_column($item, 'subtotal'));
    $diskon   = min(100, ($promo['diskon_persen'] ?? 0) + diskon_member($userId, $id));
    $total    = (int)round($subtotal * (100 - $diskon) / 100);

    $pdo->beginTransaction();   // header + semua barang disimpan bersama, atau batal semua
    if ($id) {
        q("UPDATE transaksi
           SET user_id = ?, promo_id = ?, diskon_persen = ?, total = ?, catatan = ?
           WHERE id = ?",
          [$userId, $promo['id'] ?? null, $diskon, $total, trim($_POST['catatan']), $id]);
        q("DELETE FROM transaksi_item WHERE transaksi_id=?", [$id]);
    } else {
        $kode = 'HL' . date('ymdHis') . rand(10, 99);
        q("INSERT INTO transaksi(kode, user_id, promo_id, diskon_persen, total, catatan)
           VALUES(?, ?, ?, ?, ?, ?)",
          [$kode, $userId, $promo['id'] ?? null, $diskon, $total, trim($_POST['catatan'])]);
        $id = $pdo->lastInsertId();
    }
    foreach ($item as $i) {
        q("INSERT INTO transaksi_item(transaksi_id, layanan_id, nama, harga_satuan, jumlah, subtotal)
           VALUES(?, ?, ?, ?, ?, ?)",
          [$id, $i['layanan']['id'], $i['layanan']['nama'],
           $i['layanan']['harga'], $i['jumlah'], $i['subtotal']]);
    }
    $pdo->commit();

    if (isset($kode)) {
        $pesan    = "Cucian $kode sudah kami terima. Total " . rp($total) . ".";
        $terkirim = notif($userId, $pesan, true);

        flash("Transaksi $kode tersimpan. Total " . rp($total) . "." . info_wa($terkirim));
    } else {
        flash('Transaksi diperbarui.');
    }
    go('transaksi.php');
}

// ---------- 4. DATA TAMPILAN ----------
// Mode edit: ambil transaksi dan barang-barangnya
$edit     = null;
$itemEdit = [];

if (isset($_GET['edit'])) {
    $edit = q("SELECT * FROM transaksi WHERE id=?", [$_GET['edit']])->fetch();

    if ($edit) {
        $itemEdit = q("SELECT layanan_id, jumlah FROM transaksi_item WHERE transaksi_id=?",
                      [$edit['id']])->fetchAll();
    }
}

$pelanggan = q("SELECT id, nama, no_hp FROM users WHERE role='customer' ORDER BY nama")->fetchAll();
$layanan   = q("SELECT * FROM layanan WHERE aktif=1 ORDER BY nama")->fetchAll();

$promo = q("SELECT * FROM promo
            WHERE aktif = 1
              AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURDATE())
            ORDER BY judul")->fetchAll();

// Daftar transaksi (boleh difilter menurut status)
$filter = $_GET['status'] ?? '';
$sql    = "SELECT t.*, u.nama pelanggan, u.no_hp, " . RINGKAS . " barang
           FROM transaksi t
           JOIN users u ON u.id = t.user_id "
        . ($filter ? "WHERE t.status = ? " : "")
        . "ORDER BY t.id DESC LIMIT 100";

$daftar = q($sql, $filter ? [$filter] : [])->fetchAll();

head('Transaksi', 'admin');
?>
<div class="row g-4">

  <!-- ============ FORM TRANSAKSI (boleh banyak barang) ============ -->
  <div class="col-xl-4">
    <div class="card p-3">

      <h5><?= $edit ? 'Edit transaksi ' . e($edit['kode']) : 'Input transaksi baru' ?></h5>

      <?php if (!$pelanggan): ?>
        <div class="alert alert-warning">Tambahkan pelanggan terlebih dahulu.</div>
      <?php endif ?>

      <form method="post">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

        <!-- Pelanggan -->
        <label class="form-label small">Pelanggan</label>
        <select name="user_id" class="form-select mb-3" required>
          <?php foreach ($pelanggan as $p): ?>
            <option value="<?= $p['id'] ?>" <?= sel($edit['user_id'] ?? 0, $p['id']) ?>>
              <?= e($p['nama'] . ' - ' . $p['no_hp']) ?>
            </option>
          <?php endforeach ?>
        </select>

        <!-- Daftar barang (baris ditambah lewat JavaScript di bawah) -->
        <label class="form-label small">Barang / layanan</label>
        <div id="daftar-item"></div>

        <button type="button" id="tambah-item" class="btn btn-sm btn-outline-primary mb-2">
          <i class="bi bi-plus-lg"></i> Tambah barang
        </button>

        <div class="d-flex justify-content-between fw-bold mb-3">
          <span>Subtotal</span>
          <span id="subtotal">Rp 0</span>
        </div>

        <!-- Promo -->
        <label class="form-label small">
          Promo
          <span class="text-muted">(member 5+ transaksi otomatis diskon 5%)</span>
        </label>
        <select name="promo_id" class="form-select mb-2">
          <option value="">Tanpa promo</option>
          <?php foreach ($promo as $p): ?>
            <option value="<?= $p['id'] ?>" <?= sel($edit['promo_id'] ?? 0, $p['id']) ?>>
              <?= e($p['judul']) ?> (<?= $p['diskon_persen'] ?>%)
            </option>
          <?php endforeach ?>
        </select>

        <input name="catatan" class="form-control mb-3" placeholder="Catatan (opsional)"
               value="<?= e($edit['catatan'] ?? '') ?>">

        <button name="simpan" class="btn btn-primary">Simpan transaksi</button>
        <?php if ($edit): ?>
          <a href="transaksi.php" class="btn btn-light">Batal</a>
        <?php endif ?>
      </form>

      <!-- Cetakan satu baris barang (disalin oleh JavaScript) -->
      <template id="tpl-item">
        <div class="item-row">

          <select name="layanan_id[]" class="form-select">
            <option value="">Pilih layanan</option>
            <?php foreach ($layanan as $l): ?>
              <option value="<?= $l['id'] ?>" data-harga="<?= $l['harga'] ?>">
                <?= e($l['nama']) ?> (<?= rp($l['harga']) ?>/<?= $l['satuan'] ?>)
              </option>
            <?php endforeach ?>
          </select>

          <input name="jumlah[]" class="form-control" inputmode="decimal" placeholder="Jml">

          <button type="button" class="btn btn-outline-danger btn-hapus" title="Hapus baris">
            <i class="bi bi-x-lg"></i>
          </button>

          <div class="item-sub small text-muted">Rp 0</div>

        </div>
      </template>

    </div>
  </div>

  <!-- ============ DAFTAR TRANSAKSI ============ -->
  <div class="col-xl-8">

    <!-- Filter status -->
    <div class="mb-3 d-flex gap-1 flex-wrap">
      <a class="btn btn-sm <?= $filter ? 'btn-outline-primary' : 'btn-primary' ?>"
         href="transaksi.php">Semua</a>

      <?php foreach (STATUS as $s): ?>
        <a class="btn btn-sm <?= $filter == $s ? 'btn-primary' : 'btn-outline-primary' ?>"
           href="?status=<?= $s ?>"><?= $s ?></a>
      <?php endforeach ?>
    </div>

    <div class="card table-responsive">
      <table class="table rtable align-middle mb-0">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Pelanggan</th>
            <th>Barang</th>
            <th>Total</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>

          <?php foreach ($daftar as $t):
              // Teks untuk tombol WhatsApp manual
              $teks = "Halo {$t['pelanggan']}, cucian {$t['kode']} ({$t['barang']}) "
                    . "statusnya: {$t['status']}.";

              if ($t['status'] == 'Selesai') {
                  $teks .= ' Sudah bisa diambil. Total ' . rp($t['total']) . '.';
              }

              $teks .= ' - ' . NAMA_USAHA;
          ?>
            <tr>
              <td>
                <?= e($t['kode']) ?>
                <div class="small text-muted"><?= tgl($t['tgl_masuk']) ?></div>
              </td>

              <td><?= e($t['pelanggan']) ?></td>

              <td><?= e($t['barang']) ?></td>

              <td>
                <?= rp($t['total']) ?>
                <?php if ($t['diskon_persen']): ?>
                  <div class="small text-muted">diskon <?= $t['diskon_persen'] ?>%</div>
                <?php endif ?>
              </td>

              <!-- Ubah status -->
              <td>
                <form method="post" class="status-form">
                  <input type="hidden" name="id" value="<?= $t['id'] ?>">

                  <select name="status" class="form-select form-select-sm">
                    <?php foreach (STATUS as $s): ?>
                      <option <?= sel($t['status'], $s) ?>><?= $s ?></option>
                    <?php endforeach ?>
                  </select>

                  <button name="ubah_status" class="btn btn-sm btn-sun">Ubah</button>
                </form>
              </td>

              <!-- Tombol aksi -->
              <td>
                <div class="aksi">

                  <a class="btn btn-sm btn-outline-secondary" target="_blank"
                     title="Cetak struk" href="struk.php?id=<?= $t['id'] ?>">
                    <i class="bi bi-receipt"></i>
                  </a>

                  <?php if (wa_no($t['no_hp'])): ?>
                    <a class="btn btn-sm btn-success" target="_blank" title="Kirim lewat WhatsApp"
                       href="https://wa.me/<?= wa_no($t['no_hp']) ?>?text=<?= urlencode($teks) ?>">
                      <i class="bi bi-whatsapp"></i>
                    </a>
                  <?php endif ?>

                  <a class="btn btn-sm btn-outline-primary" href="?edit=<?= $t['id'] ?>">Edit</a>

                  <?= delform($t['id']) ?>

                </div>
              </td>
            </tr>
          <?php endforeach ?>

          <?php if (!$daftar): ?>
            <tr><td colspan="6" class="text-muted">Belum ada transaksi.</td></tr>
          <?php endif ?>

        </tbody>
      </table>
    </div>

  </div>
</div>

<script>
  // ===== Baris barang dinamis: tambah / hapus baris, subtotal dihitung langsung =====
  const daftar = document.getElementById('daftar-item');
  const tpl    = document.getElementById('tpl-item');
  const awal   = <?= json_encode($itemEdit) ?>;     // barang yang sudah ada (mode edit)

  const rupiah = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

  // Hitung subtotal tiap baris dan total semua baris
  function hitung() {
    let total = 0;

    daftar.querySelectorAll('.item-row').forEach(baris => {
      const pilihan = baris.querySelector('select').selectedOptions[0];
      const harga   = +(pilihan?.dataset.harga || 0);
      const jumlah  = parseFloat(baris.querySelector('input').value.replace(',', '.')) || 0;

      baris.querySelector('.item-sub').textContent = rupiah(harga * jumlah);
      total += harga * jumlah;
    });

    document.getElementById('subtotal').textContent = rupiah(total);
  }

  // Tambah satu baris barang (boleh langsung diisi)
  function tambah(layananId = '', jumlah = '') {
    const baris = tpl.content.cloneNode(true);

    baris.querySelector('select').value = layananId;
    baris.querySelector('input').value  = jumlah ? parseFloat(jumlah) : '';

    daftar.appendChild(baris);
    hitung();
  }

  document.getElementById('tambah-item').onclick = () => tambah();

  daftar.addEventListener('input',  hitung);
  daftar.addEventListener('change', hitung);

  // Hapus baris (minimal tersisa satu baris)
  daftar.addEventListener('click', e => {
    const tombol = e.target.closest('.btn-hapus');

    if (tombol && daftar.children.length > 1) {
      tombol.closest('.item-row').remove();
      hitung();
    }
  });

  // Saat halaman dibuka: tampilkan barang lama (edit) atau satu baris kosong (baru)
  (awal.length ? awal : [{}]).forEach(i => tambah(i.layanan_id, i.jumlah));
</script>
<?php foot();
