<?php
require '../inc.php';
guard('admin');

// Teks WhatsApp untuk setiap perubahan status (selalu disertai tautan lacak)
function pesan_status($t) {
    $pesan = [
        'Antri'   => "Pesanan {$t['kode']} sudah kami terima dan masuk antrian.",

        'Proses'  => "Pesanan {$t['kode']} sedang kami proses.",

        'Selesai' => "Pesanan {$t['kode']} sudah selesai dan bisa diambil. "
                   . "Total tagihan " . rp($t['total']) . ". "
                   . "Isi review singkat di website kami untuk mendapatkan kode promo.",

        'Ambil'   => "Pesanan {$t['kode']} sudah diambil. Terima kasih! "
                   . "Jangan lupa isi review untuk mendapatkan kode promo.",
    ][$t['status']];

    return $pesan . "\nLacak pesanan: " . URL_SITUS . "/?kode={$t['kode']}#lacak";
}

function info_wa($berhasil) {
    if (WA_TOKEN === '') {
        return '';
    }

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

// ---------- 1. HAPUS (kode promo yang terpakai dikembalikan) ----------
if (isset($_POST['del'])) {
    q("UPDATE review SET promo_dipakai=0, dipakai_transaksi_id=NULL WHERE dipakai_transaksi_id=?",
      [$_POST['del']]);
    q("DELETE FROM transaksi WHERE id=?", [$_POST['del']]);

    flash('Transaksi dihapus.');
    go('transaksi.php');
}

// ---------- 2. UBAH STATUS + kirim WhatsApp ke pelanggan ----------
if (isset($_POST['ubah_status'])) {
    $t = q("SELECT * FROM transaksi WHERE id=?", [$_POST['id']])->fetch();

    if ($t && in_array($_POST['status'], STATUS)) {
        q("UPDATE transaksi
           SET status = ?, tgl_selesai = IF(? = 'Selesai', NOW(), tgl_selesai)
           WHERE id = ?",
          [$_POST['status'], $_POST['status'], $t['id']]);

        $t['status'] = $_POST['status'];
        $terkirim    = wa_kirim($t['pelanggan_id'], pesan_status($t));

        flash("Status {$t['kode']} diubah menjadi {$t['status']}." . info_wa($terkirim));
    }

    go('transaksi.php');
}

// ---------- 3. SIMPAN (baru / edit) dengan banyak barang ----------
if (isset($_POST['simpan'])) {
    $id          = (int)$_POST['id'];
    $pelangganId = (int)$_POST['pelanggan_id'];       // 0 = pelanggan baru
    $balik       = 'transaksi.php' . ($id ? "?edit=$id" : '');
    $item        = ambil_item();

    if (!$item) {
        flash('Tambahkan minimal satu barang dengan jumlah lebih dari 0.', 'danger');
        go($balik);
    }

    if ($pelangganId == 0 && trim($_POST['nama_baru'] ?? '') === '') {
        flash('Isi nama pelanggan baru.', 'danger');
        go($balik);
    }

    // Kode promo: hanya sah bila diberikan kepada pelanggan ini lewat review dan belum dipakai
    $promo     = null;
    $kodePromo = strtoupper(trim($_POST['kode_promo'] ?? ''));

    if ($kodePromo !== '') {
        $promo = $pelangganId ? cek_promo($kodePromo, $pelangganId, $id) : null;

        if (!$promo) {
            flash('Kode promo tidak valid, sudah dipakai, atau bukan milik pelanggan ini.', 'danger');
            go($balik);
        }
    }

    // Total = jumlah semua barang, dikurangi diskon promo + diskon member
    $subtotal = array_sum(array_column($item, 'subtotal'));
    $member   = $pelangganId ? diskon_member($pelangganId, $id) : 0;
    $diskon   = min(100, ($promo['diskon_persen'] ?? 0) + $member);
    $total    = (int)round($subtotal * (100 - $diskon) / 100);

    $pdo->beginTransaction();    // semua disimpan bersama, atau batal semua

    // Pelanggan baru dicatat dulu
    if ($pelangganId == 0) {
        q("INSERT INTO pelanggan(nama, no_hp) VALUES(?, ?)",
          [trim($_POST['nama_baru']), trim($_POST['hp_baru'] ?? '')]);
        $pelangganId = $pdo->lastInsertId();
    }

    if ($id) {
        q("UPDATE transaksi
           SET pelanggan_id = ?, promo_id = ?, diskon_persen = ?, total = ?, catatan = ?
           WHERE id = ?",
          [$pelangganId, $promo['id'] ?? null, $diskon, $total, trim($_POST['catatan']), $id]);

        q("DELETE FROM transaksi_item WHERE transaksi_id=?", [$id]);
    } else {
        $kode = kode_pesanan();

        q("INSERT INTO transaksi(kode, pelanggan_id, promo_id, diskon_persen, total, catatan)
           VALUES(?, ?, ?, ?, ?, ?)",
          [$kode, $pelangganId, $promo['id'] ?? null, $diskon, $total, trim($_POST['catatan'])]);

        $id = $pdo->lastInsertId();
    }

    foreach ($item as $i) {
        q("INSERT INTO transaksi_item(transaksi_id, layanan_id, nama, harga_satuan, jumlah, subtotal)
           VALUES(?, ?, ?, ?, ?, ?)",
          [$id, $i['layanan']['id'], $i['layanan']['nama'],
           $i['layanan']['harga'], $i['jumlah'], $i['subtotal']]);
    }

    // Kode promo: lepas pemakaian lama, lalu tandai kode yang baru sebagai terpakai
    q("UPDATE review SET promo_dipakai=0, dipakai_transaksi_id=NULL WHERE dipakai_transaksi_id=?", [$id]);

    if ($promo) {
        q("UPDATE review SET promo_dipakai=1, dipakai_transaksi_id=? WHERE id=?",
          [$id, $promo['review_id']]);
    }

    $pdo->commit();

    // Pesanan baru: kabari pelanggan (WhatsApp) dan tampilkan kode pesanannya
    if (isset($kode)) {
        $baru     = ['kode' => $kode, 'total' => $total, 'status' => 'Antri'];
        $terkirim = wa_kirim($pelangganId, pesan_status($baru));

        flash("Pesanan tersimpan. KODE PESANAN: $kode | Total " . rp($total) . '.' . info_wa($terkirim));
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
    $edit = q("SELECT t.*, pr.kode kode_promo
               FROM transaksi t
               LEFT JOIN promo pr ON pr.id = t.promo_id
               WHERE t.id = ?", [$_GET['edit']])->fetch();

    if ($edit) {
        $itemEdit = q("SELECT layanan_id, jumlah FROM transaksi_item WHERE transaksi_id=?",
                      [$edit['id']])->fetchAll();
    }
}

$pelanggan = q("SELECT id, nama, no_hp FROM pelanggan ORDER BY nama")->fetchAll();
$layanan   = q("SELECT * FROM layanan WHERE aktif=1 ORDER BY nama")->fetchAll();

// Daftar transaksi (boleh difilter menurut status)
$filter = $_GET['status'] ?? '';
$sql    = "SELECT t.*, p.nama pelanggan, p.no_hp, " . RINGKAS . " barang
           FROM transaksi t
           JOIN pelanggan p ON p.id = t.pelanggan_id "
        . ($filter ? "WHERE t.status = ? " : "")
        . "ORDER BY t.id DESC LIMIT 100";

$daftar = q($sql, $filter ? [$filter] : [])->fetchAll();

head('Transaksi', 'admin');
?>
<div class="row g-4">

  <!-- ============ FORM TRANSAKSI (boleh banyak barang) ============ -->
  <div class="col-xl-4">
    <div class="card p-3">

      <h5><?= $edit ? 'Edit pesanan ' . e($edit['kode']) : 'Input pesanan baru' ?></h5>

      <form method="post">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

        <!-- Pelanggan: pilih yang sudah ada atau catat pelanggan baru -->
        <label class="form-label small">Pelanggan</label>
        <select name="pelanggan_id" id="pilih-pelanggan" class="form-select mb-2">
          <?php foreach ($pelanggan as $p): ?>
            <option value="<?= $p['id'] ?>" <?= sel($edit['pelanggan_id'] ?? 0, $p['id']) ?>>
              <?= e($p['nama'] . ' - ' . $p['no_hp']) ?>
            </option>
          <?php endforeach ?>
          <option value="0">+ Pelanggan baru</option>
        </select>

        <div id="pelanggan-baru" class="mb-3" hidden>
          <input name="nama_baru" class="form-control mb-2" placeholder="Nama pelanggan baru">
          <input name="hp_baru" class="form-control" placeholder="No. WhatsApp (08xx)">
        </div>

        <!-- Daftar barang (baris ditambah lewat JavaScript di bawah) -->
        <label class="form-label small mt-2">Barang / layanan</label>
        <div id="daftar-item"></div>

        <button type="button" id="tambah-item" class="btn btn-sm btn-outline-primary mb-2">
          <i class="bi bi-plus-lg"></i> Tambah barang
        </button>

        <div class="d-flex justify-content-between fw-bold mb-3">
          <span>Subtotal</span>
          <span id="subtotal">Rp 0</span>
        </div>

        <!-- Kode promo dari review pelanggan -->
        <label class="form-label small">
          Kode promo
          <span class="text-muted">(dari review pelanggan, opsional)</span>
        </label>
        <input name="kode_promo" class="form-control mb-2 text-uppercase" placeholder="Mis. TERIMAKASIH10"
               value="<?= e($edit['kode_promo'] ?? '') ?>">

        <input name="catatan" class="form-control mb-3" placeholder="Catatan (opsional)"
               value="<?= e($edit['catatan'] ?? '') ?>">

        <button name="simpan" class="btn btn-primary">Simpan pesanan</button>
        <?php if ($edit): ?>
          <a href="transaksi.php" class="btn btn-light">Batal</a>
        <?php endif ?>
      </form>

      <div class="small text-muted mt-3">
        Diskon member 5% otomatis untuk pelanggan dengan 5+ pesanan.
      </div>

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
            <th>Kode pesanan</th>
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
              $teks = "Halo {$t['pelanggan']}, " . pesan_status($t);
          ?>
            <tr>
              <td>
                <b><?= e($t['kode']) ?></b>
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

                  <a class="btn btn-sm btn-outline-secondary"
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
  // ===== Pilihan pelanggan: tampilkan isian nama bila memilih "Pelanggan baru" =====
  const pilih     = document.getElementById('pilih-pelanggan');
  const kotakBaru = document.getElementById('pelanggan-baru');

  function aturPelanggan() {
    const baru = pilih.value === '0';

    kotakBaru.hidden = !baru;
    kotakBaru.querySelector('[name=nama_baru]').required = baru;
  }

  pilih.addEventListener('change', aturPelanggan);
  aturPelanggan();

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
