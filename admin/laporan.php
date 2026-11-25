<?php
require '../inc.php';
guard('admin');

[$jenis, $dari, $sampai] = param_laporan();
[$judul, $kolom, $baris] = data_laporan($jenis, $dari, $sampai);

$jumlah = count($baris);
$qs     = http_build_query(['jenis' => $jenis, 'dari' => $dari, 'sampai' => $sampai]);

// ---------- 1. Angka ringkasan (kartu), kalimat ringkasan, dan baris total ----------
// Setiap kartu: [judul, nilai, ikon, warna]
$kartu = [];
$foot  = null;      // baris total di bagian bawah tabel (tanpa kolom No)

if ($jenis == 'transaksi') {
    $pendapatan = array_sum(array_column($baris, 4));
    $selesai    = count(array_filter($baris, fn($b) => in_array($b[5], ['Selesai', 'Ambil'])));

    $kartu = [
        ['Total pesanan',         $jumlah,                                  'bi-receipt',      '#1363df'],
        ['Pendapatan',            rp($pendapatan),                          'bi-cash-coin',    '#22c55e'],
        ['Rata-rata per pesanan', rp($jumlah ? $pendapatan / $jumlah : 0),  'bi-graph-up',     '#18b5d4'],
        ['Selesai / diambil',     $selesai,                                 'bi-check2-circle', '#f59e0b'],
    ];

    $ringkas = "$jumlah pesanan | Pendapatan " . rp($pendapatan);
    $foot    = ['', '', '', 'TOTAL', rp($pendapatan), ''];

} elseif ($jenis == 'pelanggan') {
    $aktif   = count(array_filter($baris, fn($b) => $b[3] > 0));
    $totalTr = array_sum(array_column($baris, 3));
    $belanja = array_sum(array_column($baris, 4));

    $kartu = [
        ['Jumlah pelanggan',      $jumlah,        'bi-people',       '#1363df'],
        ['Pelanggan aktif',       $aktif,         'bi-person-check', '#22c55e'],
        ['Total transaksi',       $totalTr,       'bi-receipt',      '#18b5d4'],
        ['Total belanja',         rp($belanja),   'bi-cash-coin',    '#f59e0b'],
    ];

    $ringkas = "$jumlah pelanggan | $totalTr transaksi | Total belanja " . rp($belanja);
    $foot    = ['TOTAL', '', '', $totalTr, rp($belanja)];

} else {
    $rata    = $jumlah ? array_sum(array_column($baris, 7)) / $jumlah : 0;
    $ya      = count(array_filter($baris, fn($b) => $b[8] == 'Ya'));
    $persen  = $jumlah ? round($ya / $jumlah * 100) : 0;
    $diberi  = count(array_filter($baris, fn($b) => $b[10] != '-'));

    $kartu = [
        ['Jumlah review',         $jumlah,                      'bi-chat-heart',  '#1363df'],
        ['Rating rata-rata',      number_format($rata, 1) . ' / 5', 'bi-star-fill', '#f59e0b'],
        ['Merekomendasikan',      $persen . '%',                'bi-hand-thumbs-up', '#22c55e'],
        ['Kode promo diberikan',  $diberi,                      'bi-gift',        '#18b5d4'],
    ];

    $ringkas = "$jumlah review | Rating rata-rata " . number_format($rata, 1) . " / 5 | $persen% merekomendasikan";
}

// ---------- 2. Pengaturan tampilan ----------
$tab = [
    'transaksi' => ['Penjualan', 'bi-receipt'],
    'pelanggan' => ['Pelanggan', 'bi-people'],
    'feedback'  => ['Review',    'bi-chat-heart'],
];

$hariIni = date('Y-m-d');

$preset = [
    'Hari ini'  => [$hariIni, $hariIni],
    '7 hari'    => [date('Y-m-d', strtotime('-6 day')),  $hariIni],
    'Bulan ini' => [date('Y-m-01'),                      $hariIni],
    '30 hari'   => [date('Y-m-d', strtotime('-29 day')), $hariIni],
    'Tahun ini' => [date('Y-01-01'),                     $hariIni],
];

$periode = date('d/m/Y', strtotime($dari)) . ' - ' . date('d/m/Y', strtotime($sampai));

// Isi sel tabel di layar (angka rupiah, label status, bintang)
function tampil_sel($jenis, $i, $tipe, $nilai) {
    if ($tipe == 'rp') {
        return rp($nilai);
    }

    if ($jenis == 'transaksi' && $i == 5) {
        return badge($nilai);
    }

    if ($jenis == 'feedback' && in_array($i, [3, 4, 5, 6])) {
        return '<span class="text-warning">★</span> ' . (int)$nilai;
    }

    if ($jenis == 'feedback' && $i == 8) {
        $warna = $nilai == 'Ya' ? 'success' : 'secondary';
        return "<span class='badge rounded-pill bg-$warna'>" . e($nilai) . '</span>';
    }

    return e($nilai);
}

// Data yang dipakai JavaScript untuk membuat file PDF
$dataPdf = [
    'usaha'     => NAMA_USAHA,
    'alamat'    => ALAMAT_USAHA . ' | Telp. ' . TELP_USAHA,
    'judul'     => $judul,
    'periode'   => $periode,
    'ringkasan' => $ringkas,
    'kolom'     => array_map(fn($k) => ['label' => $k[0], 'tipe' => $k[1]], $kolom),
    'baris'     => $baris,
    'foot'      => $foot,
    'file'      => "laporan-$jenis-$dari-sd-$sampai",
];

head('Laporan', 'admin');
?>
<!-- Cetak dalam kertas A4 mendatar -->
<style>@page { size: A4 landscape; margin: 10mm; }</style>

<div class="no-print">

  <!-- ============ PILIHAN LAPORAN ============ -->
  <div class="lap-tabs mb-3">
    <?php foreach ($tab as $j => [$label, $ikon]): ?>
      <a class="lap-tab <?= $jenis == $j ? 'aktif' : '' ?>"
         href="?jenis=<?= $j ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>">
        <i class="bi <?= $ikon ?>"></i><?= $label ?>
      </a>
    <?php endforeach ?>
  </div>

  <!-- ============ FILTER PERIODE + TOMBOL UNDUH ============ -->
  <div class="card p-3 mb-3">

    <form class="row g-2 align-items-end">
      <input type="hidden" name="jenis" value="<?= e($jenis) ?>">

      <div class="col-6 col-md-auto">
        <label class="form-label small mb-1">Dari</label>
        <input type="date" name="dari" class="form-control" value="<?= e($dari) ?>">
      </div>

      <div class="col-6 col-md-auto">
        <label class="form-label small mb-1">Sampai</label>
        <input type="date" name="sampai" class="form-control" value="<?= e($sampai) ?>">
      </div>

      <div class="col-12 col-md-auto">
        <button class="btn btn-primary"><i class="bi bi-funnel"></i> Tampilkan</button>
      </div>

      <div class="col-12 col-xl d-flex gap-2 flex-wrap justify-content-xl-end">
        <button type="button" class="btn btn-outline-secondary" onclick="print()">
          <i class="bi bi-printer"></i> Cetak
        </button>

        <button type="button" class="btn btn-danger" onclick="unduhPdf()">
          <i class="bi bi-file-earmark-pdf"></i> Unduh PDF
        </button>

        <a class="btn btn-success" href="export.php?<?= $qs ?>">
          <i class="bi bi-file-earmark-excel"></i> Unduh Excel
        </a>
      </div>
    </form>

    <!-- Pilihan cepat -->
    <div class="d-flex gap-1 flex-wrap mt-3">
      <?php foreach ($preset as $nama => [$awal, $akhir]): ?>
        <a class="btn btn-sm btn-outline-primary"
           href="?jenis=<?= $jenis ?>&dari=<?= $awal ?>&sampai=<?= $akhir ?>"><?= $nama ?></a>
      <?php endforeach ?>
    </div>

  </div>

  <!-- ============ KARTU RINGKASAN ============ -->
  <div class="row g-3 mb-3">
    <?php foreach ($kartu as [$judulKartu, $nilai, $ikon, $warna]): ?>
      <div class="col-6 col-xl-3">
        <div class="card p-3 kartu-stat">
          <div class="d-flex align-items-center gap-3">
            <div class="ico" style="background:<?= $warna ?>1f; color:<?= $warna ?>">
              <i class="bi <?= $ikon ?>"></i>
            </div>
            <div style="min-width:0">
              <div class="judul"><?= $judulKartu ?></div>
              <div class="stat"><?= $nilai ?></div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach ?>
  </div>

</div>

<!-- ============ LAPORAN (bagian yang dicetak) ============ -->
<div id="area-laporan" class="card p-4 area-cetak">

  <div class="kop">
    <div class="logo-lap"><i class="bi bi-droplet-half"></i></div>
    <h4 class="mb-0"><?= e(NAMA_USAHA) ?></h4>
    <div class="small text-muted"><?= e(ALAMAT_USAHA) ?> | Telp. <?= e(TELP_USAHA) ?></div>
  </div>

  <div class="text-center mb-3">
    <h5 class="mb-1"><?= e($judul) ?></h5>
    <div class="small">Periode <?= $periode ?></div>
    <div class="fw-semibold small mt-1"><?= e($ringkas) ?></div>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-bordered align-middle mb-0 tabel-lap">

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
              <td><?= tampil_sel($jenis, $i, $kolom[$i][1], $nilai) ?></td>
            <?php endforeach ?>
          </tr>
        <?php endforeach ?>

        <?php if (!$baris): ?>
          <tr>
            <td colspan="<?= count($kolom) + 1 ?>" class="text-center text-muted py-4">
              Tidak ada data pada periode ini.
            </td>
          </tr>
        <?php endif ?>
      </tbody>

      <?php if ($foot && $baris): ?>
        <tfoot>
          <tr>
            <td></td>
            <?php foreach ($foot as $isi): ?>
              <td><?= e($isi) ?></td>
            <?php endforeach ?>
          </tr>
        </tfoot>
      <?php endif ?>

    </table>
  </div>

  <div class="small text-muted mt-3">Dicetak <?= date('d/m/Y H:i') ?></div>

</div>

<!-- Pustaka PDF (versi dikunci). jsPDF membuat PDF, AutoTable menyusun tabel dan membagi halaman. -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
  // Data laporan dari server (aman untuk disisipkan ke HTML)
  const LAPORAN = <?= json_encode($dataPdf, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;

  // Format nilai sesuai tipe kolom. Karakter di luar Latin-1 dibuang karena tidak didukung font PDF.
  function format(nilai, tipe) {
    if (tipe === 'rp')      return 'Rp ' + (Number(nilai) || 0).toLocaleString('id-ID');
    if (tipe === 'angka')   return String(parseInt(nilai, 10) || 0);
    if (tipe === 'desimal') return (parseFloat(nilai) || 0).toFixed(1);

    return String(nilai ?? '').replace(/[^\x00-\xFF]/g, '');
  }

  // Unduh PDF: tabel dibuat langsung oleh AutoTable (bukan tangkapan layar),
  // sehingga kolom tidak terpotong, baris tidak terbelah, dan judul tabel diulang di setiap halaman.
  function unduhPdf() {
    const { jsPDF } = window.jspdf;
    const doc    = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
    const lebar  = doc.internal.pageSize.getWidth();
    const tinggi = doc.internal.pageSize.getHeight();
    const tengah = lebar / 2;
    const L      = LAPORAN;

    // --- Kop laporan ---
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(16);
    doc.text(L.usaha, tengah, 14, { align: 'center' });

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(format(L.alamat), tengah, 19.5, { align: 'center' });

    doc.setDrawColor(190);
    doc.line(14, 23, lebar - 14, 23);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.text(L.judul, tengah, 30, { align: 'center' });

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9.5);
    doc.text('Periode ' + L.periode, tengah, 35.5, { align: 'center' });
    doc.text(format(L.ringkasan), tengah, 40.5, { align: 'center' });

    // --- Isi tabel ---
    const jumlahKolom = L.kolom.length + 1;                       // ditambah kolom No
    const kepala = [['No'].concat(L.kolom.map(k => k.label))];

    let isi = L.baris.map((baris, no) =>
      [String(no + 1)].concat(baris.map((nilai, i) => format(nilai, L.kolom[i].tipe)))
    );

    if (isi.length === 0) {
      isi = [[{ content: 'Tidak ada data pada periode ini.', colSpan: jumlahKolom,
                styles: { halign: 'center', textColor: 120 } }]];
    }

    // Kolom angka rata kanan, kolom No di tengah
    const gayaKolom = { 0: { halign: 'center', cellWidth: 10 } };

    L.kolom.forEach((k, i) => {
      if (['rp', 'angka', 'desimal'].includes(k.tipe)) {
        gayaKolom[i + 1] = { halign: 'right' };
      }
    });

    const opsi = {
      startY: 45,
      head: kepala,
      body: isi,
      theme: 'grid',
      margin: { left: 14, right: 14, bottom: 16 },
      styles: { fontSize: 8, cellPadding: 1.8, valign: 'middle', overflow: 'linebreak', lineColor: [210, 218, 228], lineWidth: 0.1 },
      headStyles: { fillColor: [11, 37, 69], textColor: 255, halign: 'center', fontStyle: 'bold' },
      alternateRowStyles: { fillColor: [245, 248, 252] },
      columnStyles: gayaKolom,
      showHead: 'everyPage',
      showFoot: 'lastPage',
      rowPageBreak: 'avoid'
    };

    // Baris total di akhir tabel (bila ada)
    if (L.foot && L.baris.length) {
      opsi.foot = [[''].concat(L.foot.map(isiFoot => format(isiFoot)))];
      opsi.footStyles = { fillColor: [232, 241, 255], textColor: 20, fontStyle: 'bold' };
    }

    if (typeof doc.autoTable === 'function') {
      doc.autoTable(opsi);
    } else if (window.jspdfAutoTable) {
      window.jspdfAutoTable.autoTable(doc, opsi);
    } else {
      alert('Pustaka PDF belum termuat. Periksa koneksi internet lalu coba lagi.');
      return;
    }

    // --- Nomor halaman dan waktu cetak di tiap halaman ---
    const jumlahHalaman = doc.getNumberOfPages();
    const waktuCetak    = new Date().toLocaleString('id-ID');

    doc.setFontSize(8);
    doc.setTextColor(120);

    for (let h = 1; h <= jumlahHalaman; h++) {
      doc.setPage(h);
      doc.text('Dicetak ' + waktuCetak, 14, tinggi - 8);
      doc.text('Halaman ' + h + ' dari ' + jumlahHalaman, lebar - 14, tinggi - 8, { align: 'right' });
    }

    doc.save(L.file + '.pdf');
  }
</script>
<?php foot();
