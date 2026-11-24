<?php
require '../inc.php';
guard('admin');

[$jenis, $dari, $sampai] = param_laporan();
[$judul, $kolom, $baris] = data_laporan($jenis, $dari, $sampai);

$qs     = http_build_query(['jenis' => $jenis, 'dari' => $dari, 'sampai' => $sampai]);
$jumlah = count($baris);

// Ringkasan di atas tabel
if ($jenis == 'transaksi') {
    $ringkas = "$jumlah transaksi | Pendapatan " . rp(array_sum(array_column($baris, 4)));
} elseif ($jenis == 'pelanggan') {
    $ringkas = "$jumlah pelanggan | Total belanja " . rp(array_sum(array_column($baris, 4)));
} else {
    $rata    = $jumlah ? array_sum(array_column($baris, 7)) / $jumlah : 0;
    $ringkas = "$jumlah review | Rating rata-rata " . number_format($rata, 1) . " / 5";
}

$tab = [
    'transaksi' => 'Transaksi',
    'pelanggan' => 'Pelanggan',
    'feedback'  => 'Review',
];

head('Laporan', 'admin');
?>

<!-- Bagian ini tidak ikut tercetak -->
<div class="no-print">

  <ul class="nav nav-pills mb-3 gap-1">
    <?php foreach ($tab as $j => $label): ?>
      <li class="nav-item">
        <a class="nav-link <?= $jenis == $j ? 'active' : '' ?>"
           href="?jenis=<?= $j ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>"><?= $label ?></a>
      </li>
    <?php endforeach ?>
  </ul>

  <form class="row g-2 mb-3 align-items-end">
    <input type="hidden" name="jenis" value="<?= e($jenis) ?>">

    <div class="col-6 col-md-auto">
      <label class="form-label small mb-1">Dari</label>
      <input type="date" name="dari" class="form-control" value="<?= e($dari) ?>">
    </div>

    <div class="col-6 col-md-auto">
      <label class="form-label small mb-1">Sampai</label>
      <input type="date" name="sampai" class="form-control" value="<?= e($sampai) ?>">
    </div>

    <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
      <button class="btn btn-primary">
        <i class="bi bi-funnel"></i> Tampilkan
      </button>

      <button type="button" class="btn btn-outline-secondary" onclick="print()">
        <i class="bi bi-printer"></i> Cetak
      </button>

      <button type="button" class="btn btn-danger" onclick="unduhPdf()">
        <i class="bi bi-file-earmark-pdf"></i> PDF
      </button>

      <a class="btn btn-success" href="export.php?<?= $qs ?>">
        <i class="bi bi-file-earmark-excel"></i> Excel
      </a>
    </div>
  </form>

</div>

<!-- Bagian ini yang dicetak / dijadikan PDF -->
<div id="area-laporan" class="card p-4">

  <div class="text-center mb-3">
    <h4 class="mb-0"><?= e(NAMA_USAHA) ?></h4>
    <div class="small text-muted"><?= e(ALAMAT_USAHA) ?> | Telp. <?= e(TELP_USAHA) ?></div>
    <hr>
    <h5 class="mb-1"><?= e($judul) ?></h5>
    <div class="small">
      Periode <?= date('d/m/Y', strtotime($dari)) ?> - <?= date('d/m/Y', strtotime($sampai)) ?>
    </div>
    <div class="fw-semibold mt-1"><?= e($ringkas) ?></div>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-bordered mb-0">
      <thead>
        <tr>
          <th>No</th>
          <?php foreach ($kolom as $k): ?>
            <th><?= e($k[0]) ?></th>
          <?php endforeach ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($baris as $no => $b): ?>
          <tr>
            <td><?= $no + 1 ?></td>
            <?php foreach ($b as $i => $nilai): ?>
              <td><?= $kolom[$i][1] == 'rp' ? rp($nilai) : e($nilai) ?></td>
            <?php endforeach ?>
          </tr>
        <?php endforeach ?>

        <?php if (!$baris): ?>
          <tr>
            <td colspan="<?= count($kolom) + 1 ?>" class="text-muted text-center">
              Tidak ada data pada periode ini.
            </td>
          </tr>
        <?php endif ?>
      </tbody>
    </table>
  </div>

  <div class="small text-muted mt-3">Dicetak <?= date('d/m/Y H:i') ?></div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
  // Unduh PDF langsung dari tampilan laporan (A4 landscape)
  function unduhPdf() {
    const area = document.getElementById('area-laporan');

    area.style.width = '1100px';     // lebar tetap agar tabel tidak terpotong

    html2pdf().set({
      margin:      8,
      filename:    'laporan-<?= $jenis ?>-<?= $dari ?>-sd-<?= $sampai ?>.pdf',
      html2canvas: { scale: 2 },
      jsPDF:       { unit: 'mm', format: 'a4', orientation: 'landscape' }
    }).from(area).save().then(() => {
      area.style.width = '';
    });
  }
</script>
<?php foot();
