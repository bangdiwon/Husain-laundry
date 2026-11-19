<?php
// ============================================================
// STRUK TRANSAKSI (kertas 80mm)
// - Cetak      : tinggi kertas dihitung otomatis dari isi struk
// - Unduh PDF  : ukuran PDF = 80mm x tinggi struk, tanpa sisa kertas
// $batasUser diisi saat dibuka pelanggan, agar hanya struk miliknya yang tampil
// ============================================================
function tampil_struk($id, $batasUser = null) {

    // 1. Ambil data transaksi
    $sql = "SELECT t.*, u.nama pelanggan
            FROM transaksi t
            JOIN users u ON u.id = t.user_id
            WHERE t.id = ?";
    $param = [$id];

    if ($batasUser) {
        $sql    .= " AND t.user_id = ?";
        $param[] = $batasUser;
    }

    $t = q($sql, $param)->fetch();

    if (!$t) {
        http_response_code(404);
        exit('Struk tidak ditemukan.');
    }

    // 2. Ambil barang dan hitung diskon
    $item     = q("SELECT * FROM transaksi_item WHERE transaksi_id = ?", [$id])->fetchAll();
    $subtotal = array_sum(array_column($item, 'subtotal'));
    $potongan = $subtotal - $t['total'];
    ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Struk <?= e($t['kode']) ?></title>

  <style>
    * {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    body {
      margin: 0;
      padding: 16px;
      background: #e9eef3;
      color: #000;
      font-family: 'Courier New', monospace;
      font-size: 12px;
      line-height: 1.35;
    }

    /* Lebar struk tetap 80mm di layar maupun saat dicetak */
    .struk {
      width: 80mm;
      margin: 0 auto;
      padding: 5mm 4mm;
      background: #fff;
      box-shadow: 0 4px 20px rgba(0, 0, 0, .15);
    }

    .tengah { text-align: center; }
    .tebal  { font-weight: bold; }
    .kecil  { font-size: 11px; color: #333; }
    .judul  { font-size: 17px; }
    .garis  { border-top: 1px dashed #000; margin: 6px 0; }
    .baris  { display: flex; justify-content: space-between; gap: 6px; }
    .total  { font-size: 15px; font-weight: bold; }

    .tombol {
      width: 80mm;
      margin: 12px auto;
      display: flex;
      gap: 8px;
    }

    .tombol button,
    .tombol a {
      flex: 1;
      padding: 10px 4px;
      border: 0;
      border-radius: 8px;
      background: #1363df;
      color: #fff;
      font: 600 13px sans-serif;
      text-align: center;
      text-decoration: none;
      cursor: pointer;
    }

    .tombol .abu { background: #64748b; }
    .tombol .pdf { background: #dc2626; }

    @media print {
      body {
        padding: 0;
        background: #fff;
      }

      .struk {
        margin: 0;
        box-shadow: none;
        page-break-inside: avoid;
      }

      .tombol { display: none; }
    }
  </style>
</head>

<body>

<div class="struk" id="struk">

  <div class="tengah tebal judul"><?= e(NAMA_USAHA) ?></div>
  <div class="tengah kecil">
    <?= e(ALAMAT_USAHA) ?><br>
    Telp. <?= e(TELP_USAHA) ?>
  </div>

  <div class="garis"></div>

  <div class="baris"><span>No.</span>       <span class="tebal"><?= e($t['kode']) ?></span></div>
  <div class="baris"><span>Tanggal</span>   <span><?= tgl($t['tgl_masuk']) ?></span></div>
  <div class="baris"><span>Pelanggan</span> <span><?= e($t['pelanggan']) ?></span></div>
  <div class="baris"><span>Status</span>    <span><?= e($t['status']) ?></span></div>

  <div class="garis"></div>

  <?php foreach ($item as $i): ?>
    <div class="tebal"><?= e($i['nama']) ?></div>
    <div class="baris kecil">
      <span><?= (float)$i['jumlah'] ?> x <?= rp($i['harga_satuan']) ?></span>
      <span><?= rp($i['subtotal']) ?></span>
    </div>
  <?php endforeach ?>

  <div class="garis"></div>

  <div class="baris">
    <span>Subtotal</span>
    <span><?= rp($subtotal) ?></span>
  </div>

  <?php if ($potongan > 0): ?>
    <div class="baris">
      <span>Diskon <?= (int)$t['diskon_persen'] ?>%</span>
      <span>-<?= rp($potongan) ?></span>
    </div>
  <?php endif ?>

  <div class="baris total">
    <span>TOTAL</span>
    <span><?= rp($t['total']) ?></span>
  </div>

  <?php if ($t['catatan']): ?>
    <div class="garis"></div>
    <div class="kecil">Catatan: <?= e($t['catatan']) ?></div>
  <?php endif ?>

  <div class="garis"></div>

  <div class="tengah kecil">
    Terima kasih atas kepercayaan Anda.<br>
    Simpan struk ini untuk pengambilan cucian.
  </div>

</div>

<div class="tombol">
  <button onclick="print()">Cetak</button>
  <button class="pdf" onclick="unduhPdf()">Unduh PDF</button>
  <a class="abu" href="javascript:history.back()">Kembali</a>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
  const struk = document.getElementById('struk');

  // Tinggi struk dalam milimeter (96 px = 25,4 mm)
  function tinggiMm() {
    return Math.ceil(struk.getBoundingClientRect().height * 25.4 / 96) + 1;
  }

  // CETAK: set ukuran kertas = 80mm x tinggi struk sebelum dialog cetak muncul
  function aturKertas() {
    let gaya = document.getElementById('ukuran-kertas');

    if (!gaya) {
      gaya = document.createElement('style');
      gaya.id = 'ukuran-kertas';
      document.head.appendChild(gaya);
    }

    gaya.textContent = '@page { size: 80mm ' + tinggiMm() + 'mm; margin: 0; }';
  }

  window.addEventListener('beforeprint', aturKertas);
  aturKertas();

  // UNDUH PDF: halaman PDF dibuat persis 80mm x tinggi struk
  function unduhPdf() {
    struk.style.boxShadow = 'none';

    html2pdf().set({
      margin:      0,
      filename:    'struk-<?= e($t['kode']) ?>.pdf',
      image:       { type: 'jpeg', quality: 1 },
      html2canvas: { scale: 3, scrollY: 0, backgroundColor: '#ffffff' },
      jsPDF:       { unit: 'mm', format: [80, tinggiMm()], orientation: 'portrait' }
    }).from(struk).save().then(() => {
      struk.style.boxShadow = '';
    });
  }
</script>

</body>
</html>
<?php
    exit;
}
